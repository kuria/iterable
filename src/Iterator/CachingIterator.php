<?php declare(strict_types=1);

namespace Kuria\Iterable\Iterator;

/**
 * Caches keys and values yielded by an inner iterator so that they may be iterated multiple times
 *
 * - this is useful when the iterator cannot be rewinded (e.g. generators)
 * - the inner iterator must not be externally modified after it has been passed to CachingIterator
 */
class CachingIterator implements \Iterator, \Countable
{
    /** @var \Iterator|null */
    private $inner;

    /** @var array */
    private $pairs = [];

    /** @var int */
    private $offset = 0;

    function __construct(\Traversable $inner, bool $rewind = true)
    {
        // resolve aggregate iterators
        while ($inner instanceof \IteratorAggregate) {
            $inner = $inner->getIterator();
        }

        // wrap \Traversable
        if (!$inner instanceof \Iterator) {
            $inner = new \IteratorIterator($inner);
        }

        if ($rewind) {
            $inner->rewind();
        }

        $this->inner = $inner;

        if ($this->inner->valid()) {
            $this->pairs[] = [$inner->key(), $inner->current()];
        }
    }

    function valid()
    {
        return isset($this->pairs[$this->offset]);
    }

    function key()
    {
        return $this->pairs[$this->offset][0] ?? null;
    }

    function current()
    {
        return $this->pairs[$this->offset][1] ?? null;
    }

    function next()
    {
        if (!isset($this->pairs[$this->offset])) {
            // end already reached, do nothing
            return;
        }

        $nextPairOffset = $this->offset + 1;

        // if the next pair is not cached yet, try to fetch it from the inner iterator
        if (!isset($this->pairs[$nextPairOffset]) && $this->inner !== null) {
            $this->inner->next();

            if ($this->inner->valid()) {
                // cache the pair
                $this->pairs[] = [$this->inner->key(), $this->inner->current()];
            } else {
                // end reached, we don't need the inner iterator anymore
                $this->inner = null;
            }
        }

        // update offset
        $this->offset = $nextPairOffset;
    }

    function rewind()
    {
        $this->offset = 0;
    }

    function count()
    {
        if ($this->inner !== null) {
            // the end has not been reached yet

            // use count() if available
            if ($this->inner instanceof \Countable) {
                return $this->inner->count();
            }

            // iterate the remaining pairs to determine full count
            $currentOffset = $this->offset;

            try {
                while ($this->valid()) {
                    $this->next();
                }
            } finally {
                // always restore original offset
                $this->offset = $currentOffset;
            }
        }

        return count($this->pairs);
    }
}
