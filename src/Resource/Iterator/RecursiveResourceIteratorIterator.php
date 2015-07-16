<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Iterator;

use Puli\Repository\Api\ResourceIterator;
use RecursiveIteratorIterator;

/**
 * Iterates recursively over {@link RecursiveResourceIterator} instances.
 *
 * Use this iterator to iterate recursively over a recursive resource iterator:
 *
 * ```php
 * $iterator = new RecursiveResourceIteratorIterator(
 *     new ResourceCollectionIterator(
 *         $collection,
 *         ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_RESOURCE
 *     ),
 *     RecursiveResourceIteratorIterator::SELF_FIRST
 * );
 *
 * foreach ($iterator as $path => $resource) {
 *     // ...
 * }
 * ```
 *
 * The configuration of this iterator works identically to its parent class
 * {@link RecursiveIteratorIterator}.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RecursiveResourceIteratorIterator extends RecursiveIteratorIterator implements ResourceIterator
{
    /**
     * Creates a new iterator.
     *
     * @param RecursiveResourceIterator $iterator The inner iterator.
     * @param int                       $mode     The iteration mode.
     * @param int                       $flags    The iteration flags.
     *
     * @see RecursiveIteratorIterator::__construct
     */
    public function __construct(RecursiveResourceIterator $iterator, $mode = self::LEAVES_ONLY, $flags = 0)
    {
        parent::__construct($iterator, $mode, $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentResource()
    {
        return $this->getInnerIterator()->getCurrentResource();
    }
}
