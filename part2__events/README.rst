======
Events
======

From the the first iteration, we know that the Domain Model layer's only dependency is the programming language: PHP. So, how do we make it communicate with outer layers? Using Domain Event is one of the solution. And that's the solution this project prefers.

From a technical point-of-view, the *event* part of Domain *Event* has the same characteristics as GUI programming *event*s, such as ``onClick`` or ``onMouseDown``. The main different is that Domain Event is event that happened in the real-world. In this project's case, Domain Event is being used to communicate the decision made by Domain Layer to the outer layer.

Because the Domain Layer needs some object to be the information carrier, while the real-world model doesn't have that kind of real-world object, a trade-off has to be made. In the file ``Model.php``, a fake object has to be introduced: ``Eventing``. ``Eventing`` represents an event subsystem, which will be given task to distribute the delivery of Domain Event information to those who needs it.


Changelog
=========

------------
1. Model.php
------------

There are one new interface and one new class:

a. Event
--------

Event is the class that will hold the Domain Event information. Instance of `Event` will flow to many objects in other layers like blood flows in human body.

Once instantiated, `Event` object can't be changed. However, this can't be represented by PHP the programming language.

Published methods:

* ``getName()`` : Get the name of ``Event``.
* ``get()`` : Get the string-keyed information of an ``Event``.


b. Eventing
-----------

Eventing is the interface for the subsystem that will distribute the ``Event`` information throughout the system.

Unimplemented methods:

* ``raise()`` : Notify that a Domain Event just happened.
* ``receive()`` : Register a listener for a specified type of Domain Event.


---------------------
2. Infrastructure.php
---------------------

``Infrastructure.php`` is a new file which will contain the ``Infrastructure`` layer. So, now we have two layers: ``Domain Models`` and ``Infrastructure``.

In this file, there are two classes that implements `Eventing` interface:

a. InProcessEventing
--------------------

This is the simplest implementation of the ``Eventing`` subsystem. It uses simple PHP array to do its job.


b. AmqpEventing
---------------

This is "fancier" ``Eventing`` implementation. This class uses AMQP to send the ``Event`` information.


Demonstration
=============

There are two sets of demonstration for this iteration:

-------------------
First demonstration
-------------------

The first demonstration is just really simple demonstration. This demonstration consists only one file: ``main_01.php``. What this file do is just setting up Infrastructure and Model, and then finally execute some sample code ``$customer->book($interstellar, "A2");``.

How to run the first demonstration
----------------------------------

Simply execute ``php main_01.php`` on your favorite console to see it in action. If all goes well, it should print::

	Got event customer.deposit_reduced
	Got event customer.booking_succeeded

Which indicates that the Customer's deposit has been reduced and the booking has been marked as succeeded.


--------------------
Second demonstration
--------------------

The second demonstration shows the degree of extensibility of this architecture. We build an emailing subsystem which a) has task to send email to user's email address whenever there's event about him/her, and b) is completely decoupled from the core system. By choosing emailing subsystem, we can show that this architecture can be used in a real-world project.

Just for fun, we use Ruby programming language to build the emailing subsystem. Also, it can be seen that -- to build this functionality -- there's nothing to change in both ``Model.php`` and ``Infrastructure.php``.

This second demonstration contains 2 files: ``main_02.php`` and ``email_sender.rb``.


How to run the second demonstration
-----------------------------------

* Make sure that RabbitMQ is installed and running on your system.
* Make sure that "bunny" and "json" Ruby packages are installed for your Ruby environment.
* Run ``composer install`` to make sure ``PhpAmqpLib`` is installed.
* Fill in all the required parameters in both ``main_02.php`` and ``email_sender.rb``.
* Run ``ruby email_sender.rb``.
* With ``email_sender.rb`` script still running in the background, run ``php main_02.php``.

If all goes well, two emails should be sent to ``dddtix@mailinator.com`` (You can check this by going to ``mailinator.com``, and then enter ``dddtix`` as the receiver's name)
