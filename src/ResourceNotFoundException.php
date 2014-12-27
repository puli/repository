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
     * @param string    $path     The path which was not found.
     * @param int       $code     The error code.
     * @param Exception $previous The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forPath($path, $code = 0, Exception $previous = null)
    {
        return new static(sprintf(
            'The resource %s does not exist.',
            $path
        ), $code, $previous);
    }

    /**
     * Creates a new exception for a resource path and a version.
     *
     * @param int       $version  The version which was not found.
     * @param string    $path     The resource path.
     * @param int       $code     The error code.
     * @param Exception $previous The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forVersion($version, $path, $code = 0, Exception $previous = null)
    {
        return new static(sprintf(
            'The version %s does not exist for resource %s.',
            $version,
            $path
        ), $code, $previous);
    }
}
