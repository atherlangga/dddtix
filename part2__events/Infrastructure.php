<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Model.php';


////////////////////////////////////////////////////////////////////////////////
//////////////////////////////  E V E N T I N G  ///////////////////////////////

/**
 * Default implementation of Event Publishing subsystem.
 */
class InProcessEventing implements Eventing
{
    // A key-value of `string` => `array` that contains event filter as its key
    // and array of objects as its value. The value contains objects that
    // interested in the specified filter.
    private $receiversMap = array();

    public function raise(Event $event)
    {
        foreach($this->receiversMap as $criteria => $receivers) {
            if (strpos($event->getName(), $criteria) !== false) {
                foreach($receivers as $receiver) {
                    $receiver($event);
                }
            }
        }
    }

    public function receive($eventFilter, callable $callback)
    {
        $this->receiversMap[$eventFilter][] = $callback;
    }
}


///
// PhpAmqpLib is used as library to connect with the backend.
///

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * An implementation of Event Publishing subsystem using AMQP as its backend.
 * This is useful for out-of-process communication.
 */
class AmqpEventing implements Eventing
{
    private $host;
    private $port;
    private $user;
    private $password;
    private $vhost;
    private $exchangeName;

    private $channel;

    public function __construct($host, $port, $user, $password,
        $vhost, $exchangeName)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->exchangeName = $exchangeName;
    }

    public function connect()
    {
        $this->connection = new AMQPConnection(
            $this->host, $this->port,
            $this->user, $this->password, $this->vhost);

        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare(
            $this->exchangeName, 'fanout', false, false, false);
    }

    public function disconnect()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * {@inheritdoc}
     */
    public function raise(Event $event)
    {
        $messageBody = json_encode($event->toArray());
        
        $message = new AMQPMessage($messageBody,
            array('content_type' => 'text/plain', 'delivery_mode' => 2));
        $this->channel->basic_publish($message, $this->exchangeName);
    }

    /**
     * {@inheritdoc}
     */
    public function receive($eventFilter, callable $callback)
    {
        // Doesn't need one, because AMQP server will call each receiver
        // by itself.
    }
}
