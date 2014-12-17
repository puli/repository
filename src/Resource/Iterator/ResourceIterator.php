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

/**
 * An iterator over {@link Resource} objects.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceIterator extends \Iterator
{
    /**
     * Returns the resource at the current position of the iterator.
     *
     * @return Resource The resource at the current position.
     */
    public function getCurrentResource();
}
