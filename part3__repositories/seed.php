<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Infrastructure.php';

date_default_timezone_set('Asia/Jakarta');

////////////////////////////////////////////////////////////////////////////////
// SETUP INFRASTRUCTURE
////////////////////////////////////////////////////////////////////////////////

// Create the Event Subsystem, this is just use to satisfy the dependencies, and
// won't be persisted.
$eventing = new InProcessEventing();

// Create the MovieScreeningRepository using File as its backend.
$movieScreeningRepository = new FileSerializationMovieScreeningRepository(
	'./movie_screenings.txt');

// Also create the CustomerRepository
$customerRepository = new FileSerializationCustomerRepository(
	'./customers.txt', './bookings.txt', $eventing);



////////////////////////////////////////////////////////////////////////////////
// SETUP MODEL
////////////////////////////////////////////////////////////////////////////////

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

// Create two MovieScreening instances
$interstellar = new MovieScreening(
	"ITSL",
	"Interstellar",
	new DateTimeImmutable('2015-1-1'),
	$tickets);

$theHobbitsAndFiveArmies = new MovieScreening(
	"THFA",
	"The Hobbits and the Five Armies",
	new DateTimeImmutable('2015-1-2'),
	$tickets);


// Create two Customer instances
$john = new Customer('john@somewhere', array(), 100, $eventing);
$jane = new Customer('jane@somewhere', array(), 150, $eventing);



////////////////////////////////////////////////////////////////////////////////
// RUN
////////////////////////////////////////////////////////////////////////////////

// Delete and recreate the file.
unlink('./movie_screenings.txt');
touch('./movie_screenings.txt');

// Save the two movies to the repository.
$movieScreeningRepository->save($interstellar);
$movieScreeningRepository->save($theHobbitsAndFiveArmies);

// Do the same thing for Customer, the only difference is that Customer data
// is separated into two parts: Customer and Booking
unlink('./customers.txt');
unlink('./bookings.txt');
touch('./customers.txt');
touch('./bookings.txt');

$customerRepository->save($john);
$customerRepository->save($jane);

