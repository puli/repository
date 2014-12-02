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

use Puli\Repository\Resource\AttachableResourceInterface;
use Puli\Repository\Resource\Collection\ResourceCollectionInterface;

/**
 * A repository that supports the addition and removal of resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ManageableRepositoryInterface extends ResourceRepositoryInterface
{
    /**
     * Adds a new resource to the repository.
     *
     * All resources passed to this method must implement
     * {@link AttachableResourceInterface}.
     *
     * @param string                                                  $path     The path at which to add the resource.
     * @param AttachableResourceInterface|ResourceCollectionInterface $resource The resource(s) to add at that path.
     *
     * @throws InvalidPathException If the path is invalid. The path must be a
     *                              non-empty string starting with "/".
     * @throws UnsupportedResourceException If the resource is invalid.
     */
    public function add($path, $resource);

    /**
     * Removes all resources matching the given selector.
     *
     * @param string $selector A resource path or a glob pattern. Must start
     *                         with "/". "." and ".." segments in the path are
     *                         supported.
     *
     * @return integer The number of resources removed from the repository.
     *
     * @throws InvalidPathException If the selector is invalid. The selector
     *                              must be a non-empty string starting with "/".
     */
    public function remove($selector);

    /**
     * Adds a tag to all resources matching the given selector.
     *
     * @param string $selector A resource path or a glob pattern. Must start
     *                         with "/". "." and ".." segments in the path are
     *                         supported.
     * @param string $tag      A tag name.
     *
     * @return integer The number of affected resources.
     *
     * @throws InvalidPathException If the selector is invalid. The selector
     *                              must be a non-empty string starting with "/".
     * @throws \InvalidArgumentException If the tag is invalid. The tag must be
     *                                   a non-empty string.
     */
    public function tag($selector, $tag);

    /**
     * Removes tags from all resources matching the given selector.
     *
     * @param string      $selector A resource path or a glob pattern. Must
     *                              start with "/". "." and ".." segments in the
     *                              path are supported.
     * @param string|null $tag      A tag name. If null, all tags are removed.
     *
     * @return integer The number of affected resources.
     *
     * @throws InvalidPathException If the selector is invalid. The selector
     *                              must be a non-empty string starting with "/".
     * @throws \InvalidArgumentException If the tag is invalid. The tag must be
     *                                   a non-empty string.
     */
    public function untag($selector, $tag = null);
}
