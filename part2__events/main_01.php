<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Infrastructure.php';



////////////////////////////////////////////////////////////////////////
// SETUP INFRASTRUCTURE
////////////////////////////////////////////////////////////////////////

// Create the standard Eventing subsystem.
$eventing = new InProcessEventing();



////////////////////////////////////////////////////////////////////////
// SETUP MODEL
////////////////////////////////////////////////////////////////////////

// Create the ticket instances, from seat A1 through B3, with price 5
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

// Create the Customer instance, with a deposit of 100
$customer = new Customer('esddd@mailinator.com', array(), 100, $eventing);



////////////////////////////////////////////////////////////////////////
// RUN
////////////////////////////////////////////////////////////////////////

// Set up dummy receiver, in order to prove the event delivery is working.
$eventing->receive('customer', function($event) {
	echo "Got event {$event->getName()} \n";
});

// Make Customer book a ticket to Interstellar in the seat A2.
$customer->book($interstellar, "A2");
