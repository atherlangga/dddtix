<?php

//////////////////////////////////////////////////////////////////////
//////////////////////////// M O D E L ///////////////////////////////

/**
 * Object that has lifetime.
 *
 * This class is for marking purpose only.
 */
interface Entity {}

/**
 * Object that doesn't have lifetime.
 *
 * This class is for marking purpose only.
 */
interface ValueObject {}

/**
 * Ticket for a Movie. This class is really simple and should be self-explanatory.
 */
class MovieTicket implements ValueObject
{
    private $seat;
    private $price;

    public function __construct($seat, $price)
    {
        $this->seat  = $seat;
        $this->price = $price;
    }

    public function toArray() {
        $representation = array();
        $representation['seat']  = $this->seat;
        $representation['price'] = $this->price;

        return $representation;
    }

    ///////////////
    /// Getters ///

    public function getSeat() { return $this->seat; }
    public function getPrice() { return $this->price; }
}

/**
 * Book for a MovieScreening.
 */
class Booking implements Entity
{
    private $id;
    private $movieCode;
    private $seatNumber;
    private $priceAmount;
    private $paidAmount;
    private $status;

    public function __construct($id, $movieCode, $seatNumber,
        $priceAmount, $paidAmount, $status)
    {
        $this->id          = $id;
        $this->movieCode   = $movieCode;
        $this->seatNumber  = $seatNumber;
        $this->priceAmount = $priceAmount;
        $this->paidAmount  = $paidAmount;
        $this->status      = $status;
    }

    public function markAsPaid()
    {
        // When the booking is paid, the paid amount is equal to the price amount.
        $newPaidAmount = $this->priceAmount;
        $newStatus = "paid";

        $this->paidAmount = $newPaidAmount;
        $this->status = $newStatus;
    }

    public function cancel()
    {
        $newStatus = "cancelled";
        $this->status = $newStatus;
    }

    public function isPaid()
    {
        return $this->status == "paid";
    }
    
    public function isCancelled()
    {
        return $this->status == "cancelled";
    }

    public function canBePaid()
    {
        return ! $this->isCancelled() && ! $this->isPaid();
    }

    public function countRemainingPrice()
    {
        return $this->priceAmount - $this->paidAmount;
    }

    public function toArray()
    {
        $representation = array();

        $representation['id']          = $this->id;
        $representation['movieCode']   = $this->movieCode;
        $representation['seatNumber']  = $this->seatNumber;
        $representation['priceAmount'] = $this->priceAmount;
        $representation['paidAmount']  = $this->paidAmount;
        $representation['status']      = $this->status;

        return $representation;
    }

    public static function createFromMovieTicket($id, $movieCode, MovieTicket $movieTicket, $paid) {
        $booking = new Booking(
            $id,
            $movieCode,
            $movieTicket->getSeat(),
            $movieTicket->getPrice(),
            $paid,
            "paid"
        );

        return $booking;
    }


    ///////////////
    /// Getters ///

    public function getId()    { return $this->id; }
    public function getName()  { return $this->name; }
    public function getPrice() { return $this->price; }

}

/**
 * The playing of a Movie.
 */
class MovieScreening implements Entity
{
    private $movieCode;
    private $movieName;
    private $screeningDate;
    private $tickets;

    /**
     * @var array An associative array containing Ticket ID as the key
     * and boolean value that represent availability for that ticket
     * as the value.
     */
    private $availabilities = array();

    public function __construct(
        $movieCode,
        $movieName,
        DateTimeImmutable $screeningDate,
        array $tickets,
        array $availabilities = array())
    {
        $this->movieCode      = $movieCode;
        $this->movieName      = $movieName;
        $this->tickets        = $tickets;
        $this->screeningDate  = $screeningDate;
        $this->availabilities = $availabilities;

        if (empty($this->availabilities)) {
            // Mark all tickets as available
            foreach ($tickets as $ticket) {
                $this->availabilities[$ticket->getSeat()] = true;
            }
        }
    }

    /**
     * Make a booking for this MovieScreening using a specified ticket.
     *
     * @param MovieTicket $ticket The ticket to be checked for booking.
     * @return true if succeeded, else false.
     */
    public function book(MovieTicket $ticket)
    {
        // Check whether the ticket really belongs to this MovieScreening.
        if ( ! in_array($ticket, $this->tickets)) {
            return false;
        }

        // Make sure that the ticket still available.
        if ( ! $this->availabilities[$ticket->getSeat()]) {
            return false;
        }

        // Now that the checking is done, mark the seat as unavailable, ..
        $this->availabilities[$ticket->getSeat()] = false;

        // .. then returns true to indicate that the booking succeeded.
        return true;
    }

    public function getTicket($seat)
    {
        foreach ($this->tickets as $ticket) {
            if ($ticket->getSeat() == $seat) {
                return $ticket;
            }
        }

        return null;
    }

    public function getAllTickets()
    {
        return $this->tickets;
    }

    /**
     * Get all the MovieTickets that can be booked.
     *
     * @return array of MovieTicket that can still be booked.
     */
    public function getBookableTickets()
    {
        $availabilities = $this->availabilities;

        // FUNCTIONAL STYLE FTW!
        return array_reduce($this->tickets, function($result, $ticket) use ($availabilities) {
            if ($availabilities[$ticket->getSeat()]) {
                $result[] = $ticket;
            }
            return $result;
        }, array());
    }

    /**
     * Get all the MovieTickets that has been booked.
     *
     * @return array of MovieTicket that has been booked.
     */
    public function getBookedTickets()
    {
        $availabilities = $this->availabilities;

        // FUNCTIONAL STYLE FTW!
        return array_reduce($this->tickets, function($result, $ticket) use ($availabilities) {
            if ( ! $availabilities[$ticket->getSeat()]) {
                $result[] = $ticket;
            }
            return $result;
        }, array());
    }

    public function toArray()
    {
        $representation = array();
        $representation['movieCode'] = $this->movieCode;
        $representation['movieName'] = $this->movieName;
        $representation['screeningDate'] = $this->screeningDate->format('Y-m-d');
        $representation['bookableTickets'] = array_reduce($this->getBookableTickets(), function($result, $ticket) {
            $result[] = $ticket->toArray();
            return $result;
        }, array());
        $representation['bookedTickets'] = array_reduce($this->getBookedTickets(), function($result, $ticket) {
            $result[] = $ticket->toArray();
            return $result;
        }, array());

        return $representation;
    }


    ///////////////
    /// Getters ///

    public function getMovieCode()     { return $this->movieCode; }
    public function getMovieName()     { return $this->movieName; }
    public function getSeat()          { return $this->seat; }
    public function getScreeningDate() { return $this->screeningDate; }
}

class Customer implements Entity
{
    private $id;
    private $bookings;
    private $deposit;

    public function __construct($id, array $bookings, $deposit)
    {
        $this->id       = $id;
        $this->bookings = $bookings;
        $this->deposit  = $deposit;
    }

    // Rate that will be applied to user's deposit when booking is made.
    private static $BOOKING_RATE = 0.10;

    /**
     * Make a booking for a specified MovieScreening on specified seat.
     *
     * @param MovieScreening $movieScreening The movie screening that the Customer wants to attend.
     * @param string         $seat           The requested seat.
     *
     * @return true when the booking succeeded, else false.
     */
    public function book(MovieScreening $movieScreening, $seat)
    {
        // Get the MovieTicket.
        $movieTicket = $movieScreening->getTicket($seat);
        if ( ! $movieTicket) {
            return false;
        }

        // Determine the booking price.
        $bookingPrice = $movieTicket->getPrice() * self::$BOOKING_RATE;

        // Check whether the deposit is enough to make a booking.
        if ($this->deposit < $bookingPrice) {
            return false;
        }

        // Try to book the ticket.
        if ( ! $movieScreening->book($movieTicket)) {
            return false;
        }

        // Register the new booking
        $newBookingId = uniqid();
        $newBooking = Booking::createFromMovieTicket($newBookingId, 
            $movieScreening->getMovieCode(), $movieTicket, $bookingPrice);
        
        $this->bookings[$newBookingId] = $newBooking;

        // Reduce customer's deposit
        $this->deposit -= $bookingPrice;

        return true;
    }

    /**
     * Pay the remaining fee of a specified Booking.
     *
     * @param int $bookingId The Booking ID for the User to pay the remaining
     *                       fee.
     * @return true when the payment succeeded, else false.
     */
    public function pay($bookingId)
    {
        ///
        // First step, let's do several checking.
        ///

        // Make sure the to-be-paid booking is indeed booked by
        // this Customer.
        if ( ! array_key_exists($bookingId, $this->bookings)) {
            $this->eventing->raise("customer.payment_failed", array(
                "customer" => $this,
                "message"  => "Booking ID '$bookingId' is not found"
            ));

            return false;
        }

        // Make suure that the to-be-paid booking can really be paid.
        $bookingToPay = $this->bookings[$bookingId];
        if ( ! $bookingToPay->canBePaid()) {
            return false;
        }

        // Count the remaining price (because the Customer already deduced before).
        $remainingPrice = $bookingToPay->countRemainingPrice();
        if ($this->deposit < $remainingPrice) {
            return false;
        }

        ///
        // Checking is done. If we got through this line, it means that
        // it's safe to do the booking now.
        /// 

        // Mark the booking as paid, even though we haven't reduce Customer's
        // deposit.
        $bookingToPay->markAsPaid();

        // Quickly reduce the Customer's deposit.
        $this->deposit -= $remainingPrice;

        return true;
    }

    // Rate that will we applied to user's deposit when booking is cancelled.
    private static $REFUND_RATE = 0.75;

    /**
     * Cancel previously booked Booking.
     *
     * @param int $bookingId The ID of the Booking that done by the Customer.
     * @return true when succeeded, else false.
     */
    public function cancel($bookingId)
    {
        // Make sure the to-be-canceled booking is indeed booked by
        // this Customer.
        if ( ! array_key_exists($bookingId, $this->bookings)) {
            return false;
        }

        // Fetch the to-be-canceled booking to get refund.
        $bookingToCancel = $this->bookings[$bookingId];
        $refund = $bookingToCancel->getPrice() * $this->REFUND_RATE;

        // Do the refund.
        $this->deposit += $refund;

        // Finally, do the cancellation.
        unset($this->bookings[$bookingId]);

        return true;
    }

    public function toArray()
    {
        $representation = array();
        $representation['id'] = $this->id;
        $representation['bookings'] = array();
        if (!empty($this->bookings)) {
            foreach($this->bookings as $booking) {
                $representation['bookings'][] = $booking->toArray();
            }
        }
        $representation['deposit'] = $this->deposit;

        return $representation;
    }


    ///////////////
    /// Getters ///

    public function getId()      { return $this->id; }
    public function getDeposit() { return $this->deposit; }

}
