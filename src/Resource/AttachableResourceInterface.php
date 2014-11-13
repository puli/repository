<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Resource;

use Puli\ResourceRepositoryInterface;
use Puli\UnsupportedResourceException;

/**
 * A resource that can be attached to a resource repository.
 *
 * Resources must implement this interface if they want to be accepted by
 * {@link ResourceRepositoryInterface::add}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface AttachableResourceInterface extends ResourceInterface
{
    /**
     * Attaches the resource to a path in a repository.
     *
     * @param ResourceRepositoryInterface $repo The repository.
     * @param string                      $path The path in the repository.
     */
    public function attachTo(ResourceRepositoryInterface $repo, $path);

    /**
     * Detaches the resource from the repository.
     *
     * After calling this method, {@link getPath} returns `null`.
     */
    public function detach();

    /**
     * Overrides another resource with this resource.
     *
     * This method is called when two different resources are subsequentially
     * added to the same path in the same repository:
     *
     * ```php
     * use Puli\ResourceRepository;
     *
     * $repo = new ResourceRepository();
     * $repo->add('/path', $resource1);
     * $repo->add('/path', $resource2);
     *
     * // $resource->override($resource1) is called
     * ```
     *
     * @param ResourceInterface $resource The overridden resource.
     *
     * @throws UnsupportedResourceException If the resource cannot be overridden.
     */
    public function override(ResourceInterface $resource);
}
