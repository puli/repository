<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api;

use Iterator;
use Puli\Repository\Api\Resource\PuliResource;

/**
 * An iterator over {@link PuliResource} objects.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceIterator extends Iterator
{
    /**
     * Returns the resource at the current position of the iterator.
     *
     * @return PuliResource The resource at the current position.
     */
    public function getCurrentResource();
}
