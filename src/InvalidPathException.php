<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use RuntimeException;

/**
 * Thrown when an invalid resource path is passed.
 *
 * Resource paths must always be absolute (i.e. start with "/") and non-empty.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InvalidPathException extends RuntimeException
{
}
