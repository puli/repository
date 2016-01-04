<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\ChangeStream;

use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\Api\ChangeStream\VersionList;
use Puli\Repository\Api\NoVersionFoundException;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;

/**
 * A change stream stored in memory.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryChangeStream implements ChangeStream
{
    /**
     * @var array
     */
    private $versions = array();

    /**
     * {@inheritdoc}
     */
    public function append(PuliResource $resource)
    {
        if (!isset($this->versions[$resource->getPath()])) {
            $this->versions[$resource->getPath()] = array();
        }

        $this->versions[$resource->getPath()][] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($path)
    {
        unset($this->versions[$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->versions = array();
    }

    /**
     * {@inheritdoc}
     */
    public function contains($path)
    {
        return isset($this->versions[$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions($path, ResourceRepository $repository = null)
    {
        if (!isset($this->versions[$path])) {
            throw NoVersionFoundException::forPath($path);
        }

        $versions = array();

        foreach ($this->versions[$path] as $resource) {
            if (null !== $repository) {
                $resource = clone $resource;
                $resource->attachTo($repository, $path);
            }

            $versions[] = $resource;
        }

        return new VersionList($path, $versions);
    }
}
