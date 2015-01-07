<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Infrastructure.php';



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

$customerId = $argv[1];
$customer = $customerRepository->find($customerId);

if (!empty($customer)) {
	echo json_encode($customer->toArray(), JSON_PRETTY_PRINT);
}
echo "\n";