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
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Tag implements \IteratorAggregate, TagInterface
{
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

    public function getName()
    {
        return $this->name;
    }

    public function add(ResourceInterface $resource)
    {
        $this->resources->attach($resource);
        $resource->addTag($this);
    }

    public function remove(ResourceInterface $resource)
    {
        $this->resources->detach($resource);
        $resource->removeTag($this);
    }

    public function all()
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
