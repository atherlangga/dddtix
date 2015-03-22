======
Events
======

From the the first iteration, we know that the Domain Model layer's only dependency is the programming language: PHP. So, how do we make it communicate with outer layers? Using Domain Event is one of the solution. And that's the solution this project prefers.

From a technical point-of-view, Domain Event has the same characteristics as in GUI programming events, such as ``onClick`` or ``onMouseDown``. The main different is that Domain Event is event that happened on the real-world. In this project's case, Domain Event is being used to communicate the decision made by Domain Layer to the outer layer.

Because the Domain Layer needs some object to be the information carrier, while the real-world model doesn't have that kind of real-world object, a trade-off has to be made. In the file ``Model.php``, a fake object has to be introduced: ``Eventing``. ``Eventing`` represents an event subsystem, which will be given task to distribute the delivery of Domain Event information to those who needs it.


Changed Files
=============

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

There are two classes that implements `Eventing` interface:

a. InProcessEventing
--------------------

This is the simplest implementation of the ``Eventing`` subsystem. It uses simple PHP array to do its job.


b. AmqpEventing
---------------

This is "fancier" ``Eventing`` implementation. This class uses AMQP to send the ``Event`` information.


Demonstration
=============

There are two sets of example for this iteration:

---------
First set
---------

The first set of example is just really simple example. This example consists only one file: ``main_01.php``. What this file do is just is just setting up Infrastructure and Model, and then finally execute some arbitrary code ``$customer->book($interstellar, "A2");``.

Run
---

Simply execute ``php main_01.php`` on your favorite console to see it in action. If all goes well, it should print::

	Got event customer.deposit_reduced
	Got event customer.booking_succeeded

Which indicates that the Customer's deposit has been reduced and the booking has been marked as succeeded.

Please note that there no decision at all happened on the ``main_01.php``. (The rule of thumb to make sure of this is to check whether there are ``if() .. else ..`` or not)

----------
Second set
----------

The second set of example shows how extensible this design is, and to show how close the use-case is in the real-world project. We build an emailing subsystem which a) has task to send email to user's email address whenever there's event him/her, and b) is completely decoupled from the core system.

Just for fun, to prove the second point, we use Ruby programming language to build the emailing subsystem. It can be seen that, to build this functionality, there's nothing to change in both ``Model.php`` and ``Infrastructure.php``!

This second set of example contains 2 files: ``main_02.php`` and ``email_sender.rb``.


Run
---

* Make sure that RabbitMQ is installed and running on your system.
* Run ``composer install`` to make sure ``PhpAmqpLib`` is installed.
* Run ``gem install bunny`` to make sure the Ruby script can become a RabbitMQ client.
* Run ``gem install json`` to make sure the Ruby script can parse JSON string.
* Fill in all the required parameters in both ``main_02.php`` and ``email_sender.rb``.
* Run ``ruby email_sender.rb``.
* With ``email_sender.rb`` script still running in the background, run ``php main_02.php``.

If all goes well, two emails should be sent to ``dddtix@mailinator.com`` (You can check it by going to ``mailinator.com``, and then enter ``dddtix`` as the receiver's name)
