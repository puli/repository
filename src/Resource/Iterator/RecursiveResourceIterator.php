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
use RecursiveIterator;

/**
 * A resource iterator that can be iterated recursively.
 *
 * Use {@link RecursiveResourceIteratorIterator} to iterate over the iterator.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface RecursiveResourceIterator extends ResourceIterator, RecursiveIterator
{
}
