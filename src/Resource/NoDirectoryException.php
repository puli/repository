<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

use Exception;

/**
 * Thrown when a resource was expected to be a directory, but is none.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoDirectoryException extends \Exception
{
    /**
     * Creates a new exception.
     *
     * @param string    $path     The path which was supposed to be a directory.
     * @param int       $code     The error code.
     * @param Exception $previous The exception that caused this exception.
     */
    public function __construct($path, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf(
            'The path "%s" is not a directory.',
            $path
        ), $code, $previous);
    }
}
