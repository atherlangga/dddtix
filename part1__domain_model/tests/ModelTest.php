<?php

require_once __DIR__ . '/../Model.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
	public function test_customer_should_be_able_to_book_ticket()
	{
		// Create the ticket instances, from seat A1 through B3
		$tickets = array();
		for ($i=0; $i < 2; $i++) { 
			$row = ord('A') + $i;
			for ($j=0; $j < 3; $j++) { 
				$column = ord('1') + $j;
				
				$seat = chr($row) . chr($column);
				$tickets[] = new MovieTicket($seat, 10000);
			}
		}

		// Create the MovieScreening instance
		$interstellar = new MovieScreening("INTE", "Interstellar", new DateTimeImmutable('2015-1-1'), $tickets);

		// Create the Customer instance
		$customer = new Customer('angga', array(), 20000);

		////////////////////////////////////////////////////////////////////////

		// Make sure that the Customer can book.
		$this->assertTrue($customer->book($interstellar, "A2"));

		// Make sure that the Customer's deposit decreased.
		$this->assertEquals(20000 - (0.1 * 10000), $customer->getDeposit());

		// Make sure that the seat "A2" is now booked.
		$ticketForA2 = $interstellar->getTicket("A2");
		$this->assertContains($ticketForA2, $interstellar->getBookedTickets());
	}

	public function test_customer_should_not_be_able_to_book_ticket_when_deposit_is_not_enough()
	{
		// Create the ticket instances, from seat A1 through B3
		$tickets = array();
		for ($i=0; $i < 2; $i++) { 
			$row = ord('A') + $i;
			for ($j=0; $j < 3; $j++) { 
				$column = ord('1') + $j;
				
				$seat = chr($row) . chr($column);
				$tickets[] = new MovieTicket($seat, 10000);
			}
		}

		// Create the MovieScreening instance
		$interstellar = new MovieScreening("INTE", "Interstellar", new DateTimeImmutable('2015-1-1'), $tickets);

		// Create the Customer instance, with a little deposit
		$customer = new Customer('angga', array(), 10);

		////////////////////////////////////////////////////////////////////////

		// Make sure that the Customer can *NOT* book.
		$this->assertFalse($customer->book($interstellar, "A2"));

		// Make sure that the Ticket is still bookable.
		$ticketForA2 = $interstellar->getTicket("A2");
		$this->assertContains($ticketForA2, $interstellar->getBookableTickets());
	}
}


