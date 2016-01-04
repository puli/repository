<?php

/*
 * This file is part of the vendor/project package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api;

use Exception;

/**
 * Thrown when a change stream contains no version of a resource.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoVersionFoundException extends ResourceNotFoundException
{
    /**
     * Creates a new exception for a resource path.
     *
     * @param string         $path  The path which was not found.
     * @param Exception|null $cause The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forPath($path, Exception $cause = null)
    {
        return new static(sprintf(
            'Could not find any version of path %s.',
            $path
        ), 0, $cause);
    }
}
