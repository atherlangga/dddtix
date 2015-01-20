============
Domain Model
============

DDD implementation usually contains at least 3 layers. Of all those layers, Domain Model is the most important one.

The raison d'etre of a software is to solve problem for its user. Most of the time, the user's problem is linked to a very specific domain. Domain Model is the layer that models this. So, it can be said that Domain Model is the heart of the software.

Being the heart of the software, it is very important to make sure that domain logic and domain/business decision always come from this layer, not other layers. All other layers exist to support this Domain Model.

In this iteration, the Domain Model is represented in a single file: ``Model.php``. It can be seen from this file that all real-world domain behavior and properties can be represented using only pure programming language (in this case, PHP) without using any other technology (such as database). In other word, we can say that this Domain Model has only one dependency: the programming language to represent real world domain.

Minimizing dependency is very important point because it will enable enormous potential of flexibility in the future, as we'll see in the future iteration.

The other important file is ``ModelTest.php``. This file is unit test file for ``Model.php``. This file exists to verify that ``Model.php`` behaves correctly not only from technical point-of-view, but also from real-world point-of-view. As can be seen inside the file, the unit test only verifies public interfaces (notice the lowercase 'i'), not the nitty-gritty technical detail. This is done for two reasons:

1.  To make a point that because public interfaces are closer to model the real-world domain, it should be prioritized.
2.  To make future refactor easier because the unit test only "bind" a handful public interfaces.


How to
======

Setup
-----

1.  Please make sure below dependencies are satisfied:

    *   PHP 5.4 or higher installed on the machine.
    *   Composer installed on the system.

2.  Run ``composer install`` on this directory.


Unit test
---------

Simply run ``phpunit tests``.
