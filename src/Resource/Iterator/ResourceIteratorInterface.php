<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Iterator;

use Puli\Repository\Resource\ResourceInterface;

/**
 * An iterator over {@link ResourceInterface} objects.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceIteratorInterface extends \Iterator
{
    /**
     * Returns the resource at the current position of the iterator.
     *
     * @return ResourceInterface The resource at the current position.
     */
    public function getCurrentResource();
}
