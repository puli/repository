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
use Puli\Repository\Api\ChangeStream\VersionList;
use Puli\Repository\Api\Resource\PuliResource;

/**
 * Stores {@link PuliResource} objects.
 *
 * A resource repository is similar to a filesystem. It stores {@link PuliResource}
 * objects, each of which has a path in the repository:
 *
 * ```php
 * $resource = $repo->get('/css/style.css');
 * ```
 *
 * Resources may have child resources. These can be accessed with
 * {@link listChildren()}:
 *
 * ```php
 * $resource = $repo->get('/css');
 *
 * foreach ($resource->listChildren() as $name => $resource) {
 *     // ...
 * }
 * ```
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface ResourceRepository
{
    /**
     * Returns the resource at the given path.
     *
     * @param string $path The path to the resource. Must start with "/". "."
     *                     and ".." segments in the path are supported.
     *
     * @return PuliResource The resource at this path.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws InvalidArgumentException  If the path is invalid. The path must
     *                                   be a non-empty string starting with "/".
     */
    public function get($path);

    /**
     * Returns all versions of a resource.
     *
     * @param string $path The path to the resource.
     *
     * @return VersionList The versions stored for this path.
     *
     * @throws NoVersionFoundException  If no version can be found.
     * @throws InvalidArgumentException If the path is invalid. The path must
     *                                  be a non-empty string starting with "/".
     */
    public function getVersions($path);

    /**
     * Returns the resources matching a query.
     *
     * @param string $query    A resource query.
     * @param string $language The language of the query. All implementations
     *                         must support the language "glob".
     *
     * @return ResourceCollection The resources matching the query.
     *
     * @throws InvalidArgumentException     If the query is invalid.
     * @throws UnsupportedLanguageException If the language is not supported.
     */
    public function find($query, $language = 'glob');

    /**
     * Returns whether any resources match a query.
     *
     * @param string $query    A resource query.
     * @param string $language The language of the query. All implementations
     *                         must support the language "glob".
     *
     * @return bool Returns `true` if any resources exist that match the query.
     *
     * @throws InvalidArgumentException     If the query is invalid.
     * @throws UnsupportedLanguageException If the language is not supported.
     */
    public function contains($query, $language = 'glob');

    /**
     * Returns whether a resource has child resources.
     *
     * @param string $path The path to the resource. Must start with "/".
     *                     "." and ".." segments in the path are supported.
     *
     * @return bool Returns `true` if the resource has child resources.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws InvalidArgumentException  If the path is invalid. The path must
     *                                   be a non-empty string starting with "/".
     */
    public function hasChildren($path);

    /**
     * Lists the child resources of a resource.
     *
     * @param string $path The path to the resource. Must start with "/".
     *                     "." and ".." segments in the path are supported.
     *
     * @return ResourceCollection The child resources of the resource.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws InvalidArgumentException  If the path is invalid. The path must
     *                                   be a non-empty string starting with "/".
     */
    public function listChildren($path);
}
