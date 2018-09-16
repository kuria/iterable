Iterable
########

Utilities for dealing with PHP's iterators and the iterable type.

.. image:: https://travis-ci.com/kuria/iterable.svg?branch=master
  :target: https://travis-ci.com/kuria/iterable

.. contents::


Features
********

- converting iterable values to arrays
- caching iterator


Requirements
************

- PHP 7.1+


Usage
*****

``IterableHelper::toArray()``
=============================

Convert an iterable value to an array.

.. code:: php

   <?php

   use Kuria\Iterable\IterableHelper;

   $array = IterableHelper::toArray($iterable);

- if the value is already an array, it is returned unchanged
- if an iterator yields multiple values with the same key, only
  the last value will be present in the array


``IterableHelper::toList()``
============================

Convert an iterable value to an array with consecutive integer indexes.

.. code:: php

   <?php

   use Kuria\Iterable\IterableHelper;

   $list = IterableHelper::toList($iterable);

- if the value is already an array, only its values will be returned
  (keys are discarded)
- if the value is traversable, all its values will be returned


``CachingIterator``
===================

``CachingIterator`` can be used to wrap any ``\Traversable`` instance so
it can rewinded, counted and iterated multiple times.

- as the traversable is iterated, its key-value pairs are cached in memory
- the cached key-value pairs are reused for future iterations
- when the traversable is fully iterated, the internal reference to it is dropped
  (since it is no longer needed)

This is mostly useful with `generators <http://php.net/manual/en/language.generators.php>`_
or other non-rewindable traversables.

.. code:: php

   <?php

   use Kuria\Iterable\Iterator\CachingIterator;

   function generator()
   {
       yield random_int(0, 99);
       yield random_int(100, 199);
       yield random_int(200, 299);
   }

   $cachingIterator = new CachingIterator(generator());

   print_r(iterator_to_array($cachingIterator));
   print_r(iterator_to_array($cachingIterator));
   var_dump(count($cachingIterator));

Output:

::

  Array
  (
      [0] => 29
      [1] => 107
      [2] => 249
  )
  Array
  (
      [0] => 29
      [1] => 107
      [2] => 249
  )
  int(3)

.. NOTE::

   Your numbers will vary, but the output is meant to demonstrate that
   the yielded pairs have indeed been cached.
