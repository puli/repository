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

use Exception;
use RuntimeException;

/**
 * Thrown when a requested resource was not found.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceNotFoundException extends RuntimeException
{
    /**
     * Creates a new exception for a resource path.
     *
     * @param string    $path  The path which was not found.
     * @param Exception $cause The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forPath($path, Exception $cause = null)
    {
        return new static(sprintf(
            'The resource %s does not exist.',
            $path
        ), 0, $cause);
    }
}
