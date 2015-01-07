<?php

//////////////////////////////////////////////////////////////////////
///////////////////////// R E P O S I T O R Y ////////////////////////

/**
 * Storage for MovieScreening.
 */
interface MovieScreeningRepository
{
    /**
     * Fetch a specifed MovieScreening based on the its Movie Code.
     *
     * @param string $movieCode The Movie Code of the Movie Screening.
     * @return MovieScreening with the specified code if found, else null.
     */
    public function find($movieCode);

    /**
     * Find all the will-be played movies after a specified Date.
     *
     * @param DateTimeImmutable $date The marker Date.
     * @return array of matched MovieScreening.
     */
    public function findMoviesAfter(DateTimeImmutable $date);

    /**
     * Find all the movie played/playing on the specified Date.
     *
     * @param DateTimeImmutable $begin The beginning marker Date.
     * @param DateTimeImmutable $end The ending marker Date.
     *
     * @return array of matched MovieScreening.
     */
    public function findMoviesBetween(DateTimeImmutable $begin, DateTimeImmutable $end);

    /**
     * Save or update a MovieScreening.
     *
     * @param MovieScreening $movieScreening The MovieScreening to save.
     */
    public function save(MovieScreening $movieScreening);
}

/**
 * Storage for Customer.
 */
interface CustomerRepository
{
    /**
     * Fetch a specified Customer based on its ID.
     *
     * @param string $id the Customer ID to fetch.
     */
    public function find($id);

    /**
     * Save or update a Customer.
     *
     * @param Customer $customer The Customer to save.
     */
    public function save(Customer $customer);
}
