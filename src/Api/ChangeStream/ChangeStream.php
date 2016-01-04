<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api\ChangeStream;

use Puli\Repository\Api\NoVersionFoundException;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;

/**
 * Tracks different versions of a resource.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ChangeStream
{
    /**
     * Stores a new version of a resource.
     *
     * @param PuliResource $resource The resource to store.
     */
    public function append(PuliResource $resource);

    /**
     * Removes all versions stored for a path.
     *
     * @param string $path The Puli path.
     */
    public function purge($path);

    /**
     * Returns whether the stream contains any version for a path.
     *
     * @param string $path The Puli path.
     *
     * @return bool Returns `true` if a version can be found and `false` otherwise.
     */
    public function contains($path);

    /**
     * Removes all contents of the stream.
     */
    public function clear();

    /**
     * Returns all versions of a resource.
     *
     * @param string             $path       The Puli path to look for.
     * @param ResourceRepository $repository The repository to attach the
     *                                       resources to.
     *
     * @return VersionList The versions of the resource.
     *
     * @throws NoVersionFoundException If no version is found for the path.
     */
    public function getVersions($path, ResourceRepository $repository = null);
}
