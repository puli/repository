<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tag;

use Webmozart\Puli\Locator\DataStorageInterface;
use Webmozart\Puli\Resource\LazyResourceCollection;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyTag implements \IteratorAggregate, TagInterface
{
    /**
     * @var DataStorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $name;

    /**
     * @var LazyResourceCollection
     */
    private $resources;

    public function __construct(DataStorageInterface $storage, $name, LazyResourceCollection $resources)
    {
        $this->storage = $storage;
        $this->name = $name;
        $this->resources = $resources;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addResource(ResourceInterface $resource)
    {
        throw new \BadMethodCallException(
            'Tags fetched from a resource locator may not be modified.'
        );
    }

    public function removeResource(ResourceInterface $resource)
    {
        throw new \BadMethodCallException(
            'Tags fetched from a resource locator may not be modified.'
        );
    }

    /**
     * @return ResourceInterface[]
     */
    public function getResources()
    {
        return $this->resources;
    }

    public function count()
    {
        return count($this->resources);
    }

    public function getIterator()
    {
        return $this->resources;
    }
}
