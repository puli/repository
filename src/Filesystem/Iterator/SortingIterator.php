<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Iterator;

use RecursiveIterator;

/**
 * Sorts another iterator.
 *
 * You need to pass a seekable iterator to the constructor. Pass the flag
 * {@link SORT_VALUE} if you want to sort by the iterator values (the default)
 * and {@link SORT_KEY} to sort by the iterator keys.
 *
 * ```php
 * $iterator = new SortingIterator(
 *     $innerIterator,
 *     SortingIterator::SORT_KEY
 * );
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SortingIterator extends \ArrayIterator implements \RecursiveIterator
{
    /**
     * Flag: Sort by value.
     */
    const SORT_VALUE = 1;

    /**
     * Flag: Sort by key.
     */
    const SORT_KEY = 2;

    /**
     * @var \SeekableIterator
     */
    private $innerIterator;

    /**
     * @var int
     */
    private $flags;

    /**
     * Creates a sorting iterator.
     *
     * Pass the flag {@link SORT_VALUE} if you want to sort by the iterator
     * values (the default) and {@link SORT_KEY} to sort by the iterator keys.
     *
     * @param \SeekableIterator $iterator The sorted iterator.
     * @param int|null          $flags    The flags.
     */
    public function __construct(\SeekableIterator $iterator, $flags = null)
    {
        if (!($flags & (self::SORT_KEY | self::SORT_VALUE))) {
            $flags |= self::SORT_VALUE;
        }

        $this->innerIterator = $iterator;
        $this->flags = $flags;

        // If we use seek(), we need to use incrementing integers as keys
        // which are passed to seek() of the inner iterator.
        $array = iterator_to_array($iterator);

        if ($flags & self::SORT_KEY) {
            $keys = array_keys($array);

            asort($keys);

            $array = array_replace($keys, array_values($array));
        } else {
            $array = array_values($array);

            asort($array);
        }

        parent::__construct($array);
    }

    public function next()
    {
        parent::next();

        if ($this->valid()) {
            // The parent keys are the positions of the inner iterator
            $this->innerIterator->seek(parent::key());
        }
    }

    public function rewind()
    {
        parent::rewind();

        if ($this->valid()) {
            // The parent keys are the positions of the inner iterator
            $this->innerIterator->seek(parent::key());
        }
    }

    public function key()
    {
        // The parent keys are the positions of the inner iterator
        // Return the keys of the inner iterator instead
        return $this->innerIterator->key();
    }

    public function hasChildren()
    {
        return $this->innerIterator instanceof \RecursiveIterator && $this->innerIterator->hasChildren();
    }

    public function getChildren()
    {
        return new static($this->innerIterator->getChildren(), $this->flags);
    }
}
