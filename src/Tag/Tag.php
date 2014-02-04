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

use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Tag implements \IteratorAggregate, TagInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \SplObjectStorage
     */
    private $resources;

    public function __construct($name)
    {
        $this->name = $name;
        $this->resources = new \SplObjectStorage();
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
        $this->resources->attach($resource);
        $resource->addTag($this);
    }

    public function removeResource(ResourceInterface $resource)
    {
        $this->resources->detach($resource);
        $resource->removeTag($this);
    }

    public function getResources()
    {
        return iterator_to_array($this->resources);
    }

    public function getIterator()
    {
        return $this->resources;
    }

    public function count()
    {
        return count($this->resources);
    }
}
