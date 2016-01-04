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
use Webmozart\KeyValueStore\Api\KeyValueStore;

/**
 * A change stream backed by a key-value store.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class KeyValueStoreChangeStream implements ChangeStream
{
    /**
     * @var KeyValueStore
     */
    private $store;

    /**
     * @param KeyValueStore $store
     */
    public function __construct(KeyValueStore $store)
    {
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    public function append(PuliResource $resource)
    {
        $versions = $this->store->get($resource->getPath(), array());

        $versions[] = $resource;

        $this->store->set($resource->getPath(), $versions);
    }

    /**
     * {@inheritdoc}
     */
    public function purge($path)
    {
        $this->store->remove($path);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($path)
    {
        return $this->store->exists($path);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->store->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions($path, ResourceRepository $repository = null)
    {
        $versions = $this->store->get($path, array());

        if (empty($versions)) {
            throw NoVersionFoundException::forPath($path);
        }

        if (null !== $repository) {
            foreach ($versions as $key => $resource) {
                $resource = clone $resource;
                $resource->attachTo($repository);
                $versions[$key] = $resource;
            }
        }

        return new VersionList($path, $versions);
    }
}
