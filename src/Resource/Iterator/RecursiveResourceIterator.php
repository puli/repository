<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource\Iterator;

/**
 * Iterates recursively over {@link RecursiveResourceIteratorInterface} instances.
 *
 * Use this iterator to iterate recursively over a recursive resource iterator:
 *
 * ```php
 * $iterator = new RecursiveResourceIterator(
 *     new ResourceCollectionIterator(
 *         $collection,
 *         ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_RESOURCE
 *     ),
 *     RecursiveResourceIterator::SELF_FIRST
 * );
 *
 * foreach ($iterator as $path => $resource) {
 *     // ...
 * }
 * ```
 *
 * The configuration of this iterator works identically to its parent class
 * {@link \RecursiveIteratorIterator}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RecursiveResourceIterator extends \RecursiveIteratorIterator implements ResourceIteratorInterface
{
    /**
     * Creates a new iterator.
     *
     * @param RecursiveResourceIteratorInterface $iterator The inner iterator.
     * @param int                                $mode     The iteration mode.
     * @param int                                $flags    The iteration flags.
     *
     * @see \RecursiveIteratorIterator::__construct
     */
    public function __construct(RecursiveResourceIteratorInterface $iterator, $mode = self::LEAVES_ONLY, $flags = 0)
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
