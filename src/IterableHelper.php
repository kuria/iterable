<?php declare(strict_types=1);

namespace Kuria\Iterable;

abstract class IterableHelper
{
    /**
     * Convert an iterable value to an array
     *
     * - if the value is already an array, it is returned unchanged
     * - if an iterator yields multiple values with the same key, only the last value will be present in the array
     */
    static function toArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : $iterable;
    }

    /**
     * Convert an iterable value to an array with consecutive integer indexes
     *
     * - if the value is already an array, only its values will be returned (keys are discarded)
     * - if the value is traversable, all its values will be returned
     */
    static function toList(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable, false) : array_values($iterable);
    }
}
