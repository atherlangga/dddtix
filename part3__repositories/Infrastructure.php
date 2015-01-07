<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Repository.php';


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

/**
 * An implementation of Event Publishing subsystem that contains another multiple
 * backend of Event Publishing subsystem.
 */
class CompositeEventing implements Eventing
{
    private $eventings;

    public function __construct()
    {
        $this->eventings = array();
    }

    public function addEventing(Eventing $eventing)
    {
        $this->eventings[] = $eventing;
    }

    /**
     * {@inheritdoc}
     */
    public function raise(Event $event)
    {
        foreach ($this->eventings as $eventing) {
            $eventing->raise($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function receive($eventFilter, callable $callback)
    {
        foreach ($this->eventings as $eventing) {
            $eventing->receive($eventFilter, $callback);
        }
    }
}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////  R E P O S I T O R Y  /////////////////////////////

class FileSerializationMovieScreeningRepository implements MovieScreeningRepository
{
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function find($movieCode)
    {
        $allMovieScreenings = $this->fetchAllMovieScreenings();
        foreach ($allMovieScreenings as $movieScreening) {
            if ($movieScreening->getMovieCode() === $movieCode) {
                return $movieScreening;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findMoviesAfter(DateTimeImmutable $date)
    {
        $allMovieScreenings = $this->fetchAllMovieScreenings();
        $matchedMovieScreenings = array_filter($allMovieScreenings, function($current) use ($date) {
            if ($current->getScreeningDate() > $date) {
                return true;
            }
            return false;
        });

        return $matchedMovieScreenings;
    }

    /**
     * {@inheritdoc}
     */
    public function findMoviesBetween(DateTimeImmutable $begin, DateTimeImmutable $end)
    {
        $allMovieScreenings = $this->fetchAllMovieScreenings();
        $matchedMovieScreenings = array_filter($allMovieScreenings, function($current) use ($date) {
            $screeningDate = $current->getScreeningDate();
            if ($screeningDate > $begin && $screeningDate <= $end) {
                return true;
            }
            return false;
        });

        return $matchedMovieScreenings;
    }

    /**
     * {@inheritdoc}
     */
    public function save(MovieScreening $movieScreening)
    {
        ///
        // The way we're saving consist of 3 steps:
        // 1. Get all instances of MovieScreenings from the file.
        // 2. Add or replace instance of MovieScreening based on the specified
        //    movie code.
        // 3. Save all of them back to the file.
        ///

        // 1. Get all instance of MovieScreenings.
        $allMovieScreenings = $this->fetchAllMovieScreenings();
        $movieScreeningsMap = array_reduce($allMovieScreenings, function($result, $current) {
            $result[$current->getMovieCode()] = $current;
            return $result;
        }, array());

        // 2. Add or replace instance of MovieScreening based on the specified
        //    movie code.
        $movieScreeningsMap[$movieScreening->getMovieCode()] = $movieScreening;

        // 3. Save all of them back to the file.
        $movieScreeningsRawData = array_map(function($current) {
            return $current->toArray();
        }, array_values($movieScreeningsMap));
        file_put_contents($this->filePath, json_encode($movieScreeningsRawData, JSON_PRETTY_PRINT));
    }

    private function fetchAllMovieScreenings()
    {
        $allMovieScreeningsRawData = $this->fetchAllMovieScreeningsRawData();
        $allMovieScreenings = array_reduce($allMovieScreeningsRawData, function($result, $current) {
            $result[] = $this->reconstituteMovieScreening($current);
            return $result;
        }, array());

        return $allMovieScreenings;
    }

    /**
     * Fetch all of MovieScreenings raw data.
     */
    private function fetchAllMovieScreeningsRawData()
    {
        $fileContents = file_get_contents($this->filePath);
        $movieScreeningsRawData = json_decode($fileContents, true);
        if (! empty($movieScreeningsRawData)) {
            return $movieScreeningsRawData;
        }
        return array();
    }

    /**
     * Reconstruct a MovieScreening from raw array data.
     */
    private function reconstituteMovieScreening($movieScreeningRawData)
    {
        // General data.
        $movieCode = $movieScreeningRawData['movieCode'];
        $movieName = $movieScreeningRawData['movieName'];
        $screeningDate = new DateTimeImmutable($movieScreeningRawData['screeningDate']);

        // Tickets and availabilities data.
        $tickets = array();
        $availabilities = array();
        foreach ($movieScreeningRawData['bookableTickets'] as $bookableTicketRawData) {
            $bookableTicket = new MovieTicket($bookableTicketRawData['seat'], $bookableTicketRawData['price']);
            $tickets[] = $bookableTicket;
            $availabilities[$bookableTicket->getSeat()] = true;
        }
        foreach ($movieScreeningRawData['bookedTickets'] as $bookedTicketRawData) {
            $bookedTicket = new MovieTicket($bookedTicketRawData['seat'], $bookedTicketRawData['price']);
            $tickets[] = $bookedTicket;
            $availabilities[$bookedTicket->getSeat()] = false;
        }

        return new MovieScreening($movieCode, $movieName, $screeningDate,
            $tickets, $availabilities);
    }
}

class FileSerializationCustomerRepository implements CustomerRepository
{
    private $customersFilePath;
    private $bookingsFilePath;
    private $customerEventing;

    public function __construct($customersFilePath, $bookingsFilePath,
        Eventing $customerEventing)
    {
        $this->customersFilePath = $customersFilePath;
        $this->bookingsFilePath  = $bookingsFilePath;
        $this->customerEventing  = $customerEventing;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        $allCustomersRawData = $this->fetchAllCustomersRawData();
        $allBookingsRawData  = $this->fetchAllBookingsRawData();

        // Begin loop to find the Customer with specified ID.
        foreach ($allCustomersRawData as $customerRawData) {
            if ($customerRawData['id'] === $id) {

                // Found it! Let's find the bookings made by this Customer.
                $bookings = array();
                foreach ($allBookingsRawData as $bookingRawData) {
                    if ($bookingRawData['customer_id'] === $id) {
                        $booking = new Booking(
                            $bookingRawData['id'],
                            $bookingRawData['movieCode'],
                            $bookingRawData['seatNumber'],
                            $bookingRawData['priceAmount'],
                            $bookingRawData['paidAmount'],
                            $bookingRawData['status']);

                        $bookings[] = $booking;
                    }
                }

                // Now that booking complete, let's build the Customer.
                $customer = new Customer(
                    $customerRawData['id'],
                    $bookings,
                    $customerRawData['deposit'],
                    $this->customerEventing);

                return $customer;
            }
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function save(Customer $customer)
    {
        $allCustomersRawData = $this->fetchAllCustomersRawData();

        $customersMap = array_reduce($allCustomersRawData, function($result, $current) {
            $currentId = $current['id'];
            $result[$currentId] = $current;
            return $result;
        }, array());

        $customersMap[$customer->getId()] = array(
            'id'      => $customer->getId(),
            'deposit' => $customer->getDeposit(),
        );

        file_put_contents($this->customersFilePath,
            json_encode(array_values($customersMap), JSON_PRETTY_PRINT));

        $allBookingsRawData = $this->fetchAllBookingsRawData();

        $bookingsMap = array_reduce($allBookingsRawData, function($result, $current) {
            $currentId = $current['id'];
            $result[$currentId] = $current;
            return $result;
        }, array());

        foreach ($customer->getBookings() as $booking) {
            $bookingsMap[$booking->getId()] = $booking->toArray();
            $bookingsMap[$booking->getId()]['customer_id'] = $customer->getId();
        }

        file_put_contents($this->bookingsFilePath,
            json_encode(array_values($bookingsMap), JSON_PRETTY_PRINT));
    }

    private function fetchAllCustomersRawData()
    {
        $fileContent = file_get_contents($this->customersFilePath);
        $allCustomersRawData = json_decode($fileContent, true);
        if (! empty($allCustomersRawData)) {
            return $allCustomersRawData;
        }
        return array();
    }

    private function fetchAllBookingsRawData()
    {
        $fileContent = file_get_contents($this->bookingsFilePath);
        $allBookingsRawData = json_decode($fileContent, true);
        if (! empty($allBookingsRawData)) {
            return $allBookingsRawData;
        }
        return array();   
    }

}
