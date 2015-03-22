<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Infrastructure.php';

date_default_timezone_set('Asia/Jakarta');


////////////////////////////////////////////////////////////////////////
// SETUP INFRASTRUCTURE
////////////////////////////////////////////////////////////////////////

// Create the AMQP Eventing subsystem.
$eventing = new AMQPEventing('localhost', 5672, 'guest', 'guest', '/', 'event');



////////////////////////////////////////////////////////////////////////
// SETUP MODEL
////////////////////////////////////////////////////////////////////////

// Create the ticket instances, from seat A1 through B3, with uniform price of
// 5 USD.
$tickets = array();
for ($i=0; $i < 2; $i++) { 
	$row = ord('A') + $i;
	for ($j=0; $j < 3; $j++) { 
		$column = ord('1') + $j;
		
		$seat = chr($row) . chr($column);
		$tickets[] = new MovieTicket($seat, 5);
	}
}

// Create the MovieScreening instance
$interstellar = new MovieScreening(
	"INTE", "Interstellar", new DateTimeImmutable('2015-1-1'), $tickets);

// Create the Customer instance, with a deposit of 100 USD
$customer = new Customer('esddd@mailinator.com', array(), 100, $eventing);



////////////////////////////////////////////////////////////////////////
// RUN
////////////////////////////////////////////////////////////////////////

// Connect the AMQP event subsystem.
$eventing->connect();

// Make Customer book a ticket to Interstellar in the seat A2.
$customer->book($interstellar, "A2");



////////////////////////////////////////////////////////////////////////
// CLEAN UP
////////////////////////////////////////////////////////////////////////

// Disconnect AMQP because we're done.
$eventing->disconnect();
