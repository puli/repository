<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Resource\Iterator;

/**
 * A resource iterator that can be iterated recursively.
 *
 * Use {@link RecursiveResourceIterator} to iterate over the iterator.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface RecursiveResourceIteratorInterface extends ResourceIteratorInterface, \RecursiveIterator
{
}
