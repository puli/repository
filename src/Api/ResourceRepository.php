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

use InvalidArgumentException;

/**
 * Stores {@link Resource} objects.
 *
 * A resource repository is similar to a filesystem. It stores {@link Resource}
 * objects, each of which has a path in the repository:
 *
 * ```php
 * $resource = $repo->get('/css/style.css');
 * ```
 *
 * Intermediate resources implement {@link DirectoryResource}. These provide
 * access to their nested resources:
 *
 * ```php
 * $directory = $repo->get('/css');
 *
 * foreach ($directory->listDirectory() as $name => $resource) {
 *     // ...
 * }
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceRepository
{
    /**
     * Returns the resource at the given path.
     *
     * @param string   $path    The path to the resource. Must start with "/".
     *                          "." and ".." segments in the path are supported.
     * @param int|null $version The version to retrieve. Pass `1` for the first,
     *                          `2` for the second and `null` for the latest
     *                          version.
     *
     * @return Resource The resource at this path.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws InvalidArgumentException If the path is invalid. The path must be
     *                                  a non-empty string starting with "/".
     */
    public function get($path, $version = null);

    /**
     * Returns the resources matching the given selector.
     *
     * @param string $selector A resource path or a glob pattern. Must start
     *                         with "/". "." and ".." segments in the path are
     *                         supported.
     *
     * @return ResourceCollection The resources matching the selector.
     *
     * @throws InvalidArgumentException If the selector is invalid. The selector
     *                                  must be a non-empty string starting with
     *                                  "/".
     */
    public function find($selector);

    /**
     * Returns whether any resources match the given selector.
     *
     * @param string $selector A resource path or a glob pattern. Must start
     *                         with "/". "." and ".." segments in the path are
     *                         supported.
     *
     * @return bool Returns whether any resources exist that match the selector.
     *
     * @throws InvalidArgumentException If the selector is invalid. The selector
     *                                  must be a non-empty string starting with
     *                                  "/".
     */
    public function contains($selector);

    /**
     * Lists the entries of a directory.
     *
     * @param string $path The path to the directory. Must start with "/".
     *                     "." and ".." segments in the path are supported.
     *
     * @return ResourceCollection The resources in the directory.
     *
     * @throws ResourceNotFoundException If the directory cannot be found.
     * @throws NoDirectoryException If the resource is no directory.
     * @throws InvalidArgumentException If the path is invalid. The path must be
     *                                  a non-empty string starting with "/".
     */
    public function listDirectory($path);
}
