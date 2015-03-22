<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Infrastructure.php';


date_default_timezone_set('Asia/Jakarta');

////////////////////////////////////////////////////////////////////////////////
// SETUP INFRASTRUCTURE
////////////////////////////////////////////////////////////////////////////////

// Create the standard Eventing subsystem.
$standardEventing = new InProcessEventing();

// Create the AMQP Eventing subsystem.
$amqpEventing = new AMQPEventing('localhost', 5672, 'guest', 'guest', '/', 'event');

// Create a Composite Eventing subsystem.
$compositeEventing = new CompositeEventing();

// Create the MovieScreeningRepository using File as its backend.
$movieScreeningRepository = new FileSerializationMovieScreeningRepository(
	'./movie_screenings.txt');

$customerRepository = new FileSerializationCustomerRepository(
	'./customers.txt', './bookings.txt', $compositeEventing);



////////////////////////////////////////////////////////////////////////////////
// RUN
////////////////////////////////////////////////////////////////////////////////

// Combine both eventing subsystem.
$compositeEventing->addEventing($standardEventing);
$compositeEventing->addEventing($amqpEventing);

// Connect to the AMQP server.
$amqpEventing->connect();

// Get the Interstellar movie.
$interstellar = $movieScreeningRepository->find("ITSL");

// Get two dummy Customers.
$john = $customerRepository->find("john@somewhere");
$jane = $customerRepository->find("jane@somewhere");

// Make both Repositories "reacts" when User succesfully booked a seat. Note
// that we ignore all the failed event because it changes nothing.
$compositeEventing->receive('customer.booking_succeeded', 
	function($event) use ($movieScreeningRepository) {
		$movieScreening = $event->get('movieScreening');
		$movieScreeningRepository->save($movieScreening);
	});

// Also make the Customer repository reacts when the Customer's deposit reduced.
$compositeEventing->receive('customer.deposit_reduced',
	function($event) use ($customerRepository) {
		$customer = $event->get('customer');
		$customerRepository->save($customer);
	});

// Set up dummy receiver, in order to prove the event delivery is working.
$compositeEventing->receive('customer', function($event) {
	echo "Got event {$event->getName()} for customer {$event->get('customer')->getId()}\n";
});

// Make the two Customers tries to book the same seat, one of them should fail.
$jane->book($interstellar, "A2");
$john->book($interstellar, "A2");


////////////////////////////////////////////////////////////////////////
// CLEAN UP
////////////////////////////////////////////////////////////////////////

// Disconnect AMQP because we're done.
$amqpEventing->disconnect();