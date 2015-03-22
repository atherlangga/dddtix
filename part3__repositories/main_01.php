<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Infrastructure.php';

date_default_timezone_set('Asia/Jakarta');


////////////////////////////////////////////////////////////////////////////////
// SETUP INFRASTRUCTURE
////////////////////////////////////////////////////////////////////////////////

// Create the standard Eventing subsystem.
$eventing = new InProcessEventing();

// Create the MovieScreeningRepository using File as its backend.
$movieScreeningRepository = new FileSerializationMovieScreeningRepository(
	'./movie_screenings.txt');

$customerRepository = new FileSerializationCustomerRepository(
	'./customers.txt', './bookings.txt', $eventing);



////////////////////////////////////////////////////////////////////////////////
// RUN
////////////////////////////////////////////////////////////////////////////////

// Get the Interstellar movie.
$interstellar = $movieScreeningRepository->find("ITSL");

// Get two dummy Customers.
$john = $customerRepository->find("john@somewhere");
$jane = $customerRepository->find("jane@somewhere");

// Make both Repositories "reacts" when User succesfully booked a seat. Note
// that we ignore all the failed event because it changes nothing.
$eventing->receive('customer.booking_succeeded', 
	function($event) use ($movieScreeningRepository) {
		$movieScreening = $event->get('movieScreening');
		$movieScreeningRepository->save($movieScreening);
	});

// Also make the Customer repository reacts when the Customer's deposit reduced.
$eventing->receive('customer.deposit_reduced',
	function($event) use ($customerRepository) {
		$customer = $event->get('customer');
		$customerRepository->save($customer);
	});

// Set up dummy receiver, in order to prove the event delivery is working.
$eventing->receive('customer', function($event) {
	echo "Got event {$event->getName()} for customer {$event->get('customer')->getId()}\n";
});

// Make the two Customers tries to book the same seat, one of them should fail.
$jane->book($interstellar, "A2");
$john->book($interstellar, "A2");


