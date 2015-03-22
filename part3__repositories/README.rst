============
Repositories
============

Until the part 2, we only run our project in a memory. But what if we want to persist the data so the application can be run many times? That's where ``Repository`` comes in. One thing that's interesting in this part is that we won't change ``Model.php`` at all. This should show that we can separate between domain logic and persistence logic easily.


Changelog
=========

-----------------
1. Repository.php
-----------------

``Repository.php`` is a new file that contains ``Repository`` interfaces. This file's only dependency is to ``Model.php``.

There are two ``Repository`` in this file:

a. MovieScreeningRepository
---------------------------

``MovieScreeningRepository``'s main job is to store ``MovieScreening``.


b. CustomerRepository
---------------------

Well, it should also be obvious that ``CustomerRepository``'s main job is to keep ``Customer`` data :)


---------------------
2. Infrastructure.php
---------------------

Both ``Repository`` interfaces mentioned above will be implemented in the ``Infrastructure`` layer. Instead of using standard database option (such as RDMBS or even NoSQL databases), we use text file. This is done in order to show that *everything* can be data source, even if it's a text file. Of course, using text file won't be a scalable option. In the future we'll revisit this data source strategy.

The two implementation of those ``Repository`` are ``FileSerializationMovieScreeningRepository`` and ``FileSerializationCustomerRepository``. Both are using text-file as its backend and JSON string for the content type.

While ``FileSerializationMovieScreeningRepository`` uses only one file, the ``FileSerializationCustomerRepository`` needs two files: one file to store the customers data, and the other to store the bookings data. However, the public API stays simple. This should show that the using ``Repository`` we're able to hide the complexity behind the persistence technique.


Demonstration
=============

As in previous iteration, there are two sets demonstration for this iteration. However, one difference is that this iteration needs one file to seed the starting data: ``seed.php``.

---------------------------------------
First demonstration: Simple Persistence
---------------------------------------

The purpose of first demonstration is really simple: to show that the persistence works. This demonstration contains two files: ``main_01.php`` and ``viewer_01.php``. As can be seen in the file ``main_01.php``, the ``Repository`` is integrated onto the project by using the ``Eventing`` subsystem that we cover in the part 2. 

How to view the first demonstration
-----------------------------------

* Run ``php seed.php`` to reset the data. This should create three files (``movie_screenings.txt``, ``customers.txt``, and ``bookings.txt``) if those are not yet exist, or empty their contents if otherwise.

* Run ``php main_01.php`` to make some changes. If all goes well, the console should show::

	Got event customer.deposit_reduced for customer jane@somewhere
	Got event customer.booking_succeeded for customer jane@somewhere
	Got event customer.booking_failed for customer john@somewhere

* To check the current state of the ``Customer``, you can execute ``viewer_01.php`` using the format ``php viewer_01.php [user_email]``. For example, if you want to check the state of ``Customer`` with email ``jane@somewhere``, you can execute ``php viewer_01.php jane@somewhere``.

* Since the persistence is backed by JSON text files, you can even view them directly with your text editor.

* You can also play with the demo by modifying the "RUN" section in ``main_01.php``. But please let me know if you encountered any bug :).


-------------------------------------------------------------------------
Second demonstration: Command and Query Responsibility Segregation (CQRS)
-------------------------------------------------------------------------

Like the second demonstration in the previous demonstration, this demonstration also serve to measuer the degree of extensibility of the project's architecture. 

CQRS basically means that there two subsystems to serve specifically for read (the "Query" part of CQRS) and write (the "Command" part of CQRS). This two subsystems distinction must be hidden from the end-user point-of-view.

Althought this project uses simple architecture, we can even implement CQRS in it.

In this demonstration, the "Command" part role is played by the core system, while for the "Query" part we build a new subsystem using Python and Redis.

Previously, the flow of read and write are like this::
	
	Read:
	[End user] -> [read request] -> [Core system (PHP)] -> [response] -> [End user]
	Write:
	[End user] -> [write request] -> [Core system (PHP)] -> [response] -> [End user]

With CQRS, it becomes::
	
	Read:
	[End user] -> [read request] -> [Query subsystem (Python + Redis)] -> [response] -> [End user]

	Write:
	[End user] -> [write request] -> [Command subsystem (PHP)] -> [event information] -> {async1 + async2}
		{async1} -> [response] -> [End user]
		{async2} -> [RabbitMQ] -> [Query subsystem (Python + Redis)]

The file ``viewer_02.php`` is used to query the Query part :). You can compare it with ``viewer_01.php`` and see the difference.

It's also worth to mention that in order to implement CQRS in this architecture, not only we **don't** need to change the ``Domain Model`` layer, we also **don't** need to change the ``Infrastructure`` layer.


How to view the second demonstration
------------------------------------

* Make sure you have RabbitMQ server installed and running.

* Make sure you have Redis server installed and running.

* Make sure you have "json", "pika", and "redis" Python packages installed on your system.

* Run ``composer install`` to make sure that "PhpAmqpLib" and "predis" are installed.

* Run ``php seed.php`` to reset the data.

* Run ``python redis_persister.py``.

* With ``redis_persister.py`` script still running in the background, run ``php main_02.php``.

* By now, the newly modified ``Customer`` data should be stored in Redis. To check it, you can run ``php view_customer_02.php jane@somewhere``.

* Check the output above with the "official" output from the core system: ``php view_customer_01.php jane@somewhere``. The output should be equivalent JSON value (the order may be jumbled, though).

