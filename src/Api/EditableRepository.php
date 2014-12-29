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
 * A repository that supports the addition and removal of resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface EditableRepository extends ResourceRepository
{
    /**
     * Adds a new resource to the repository.
     *
     * All resources passed to this method must implement {@link Resource}.
     *
     * @param string                      $path     The path at which to add the
     *                                              resource.
     * @param Resource|ResourceCollection $resource The resource(s) to add at
     *                                              that path.
     *
     * @throws InvalidArgumentException If the path is invalid. The path must be
     *                                  a non-empty string starting with "/".
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
     * @throws InvalidArgumentException If the selector is invalid. The selector
     *                                  must be a non-empty string starting with
     *                                  "/".
     */
    public function remove($selector);
}
