<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use Puli\Resource\Collection\ResourceCollectionInterface;
use Puli\Resource\ResourceInterface;

/**
 * Stores {@link ResourceInterface} objects.
 *
 * A resource repository is similar to a filesystem. It stores
 * {@link ResourceInterface} objects, each of which has a path in the
 * repository:
 *
 * ```php
 * $resource = $repo->get('/css/style.css');
 * ```
 *
 * Intermediate resources implement {@link DirectoryResourceInterface}. These
 * provide access to their nested resources:
 *
 * ```php
 * $directory = $repo->get('/css');
 *
 * foreach ($directory->listEntries() as $name => $resource) {
 *     // ...
 * }
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceRepositoryInterface
{
    /**
     * Returns the resource at the given path.
     *
     * @param string $path The path to the resource. Must start with "/".
     *                     "." and ".." segments in the path are supported.
     *
     * @return ResourceInterface The resource at this path.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws InvalidPathException If the path is invalid. The path must be a
     *                              non-empty string starting with "/".
     */
    public function get($path);

    /**
     * Returns the resources matching the given selector.
     *
     * @param string $selector A resource path or a glob pattern. Must start
     *                         with "/". "." and ".." segments in the path are
     *                         supported.
     *
     * @return ResourceCollectionInterface The resources matching the selector.
     *
     * @throws InvalidPathException If the selector is invalid. The selector
     *                              must be a non-empty string starting with "/".
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
     * @throws InvalidPathException If the selector is invalid. The selector
     *                              must be a non-empty string starting with "/".
     */
    public function contains($selector);

    /**
     * Returns the resources with the given tag.
     *
     * @param string $tag A tag name.
     *
     * @return ResourceCollectionInterface The resources with this tag.
     *
     * @throws \InvalidArgumentException If the tag is invalid. The tag must be
     *                                   a non-empty string.
     */
    public function findByTag($tag);

    /**
     * Returns all known tags in the repository.
     *
     * @return string[] The tag names.
     */
    public function getTags();
}
