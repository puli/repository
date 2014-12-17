<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\StreamWrapper;

use RuntimeException;

/**
 * Thrown when the stream wrapper was not registered or is registered twice.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class StreamWrapperException extends RuntimeException
{
}
