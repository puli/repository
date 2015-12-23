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

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\ChangeStream\ResourceStack;

/**
 * Stream to track repositories changes and fetch previous versions of resources.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface ChangeStream
{
    /**
     * Store a version of a resource in the ChangeStream to retrieve it if needed.
     *
     * @param PuliResource $resource
     */
    public function append(PuliResource $resource);

    /**
     * Create a stack of resources for the given path.
     *
     * @param ResourceRepository $repository
     * @param string             $path
     *
     * @return ResourceStack
     */
    public function buildStack(ResourceRepository $repository, $path);
}
