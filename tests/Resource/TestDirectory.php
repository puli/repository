<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource;

use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestDirectory implements DirectoryResourceInterface
{
    private $path;

    private $overrides;

    /**
     * @var ResourceInterface[]
     */
    private $entries = array();

    public function __construct($path, array $entries = array())
    {
        $this->path = $path;

        foreach ($entries as $entry) {
            $this->add($entry);
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getName()
    {
        return basename($this->path);
    }

    public function get($name)
    {
        return $this->entries[$name];
    }

    public function contains($name)
    {
        return isset($this->entries[$name]);
    }

    public function listEntries()
    {
        return new ResourceCollection($this->entries);
    }

    public function add(ResourceInterface $entry)
    {
        $this->entries[$entry->getName()] = $entry;
    }

    public function remove($name)
    {
        unset($this->entries[$name]);
    }

    public function copyTo($path)
    {
        $copy = clone $this;
        $copy->path = $path;

        foreach ($copy->entries as $name => $entry) {
            $copy->entries[$name] = $entry->copyTo($path.'/'.$name);
        }

        return $copy;
    }

    public function override(ResourceInterface $resource)
    {
        /** @var DirectoryResourceInterface $resource */
        $copy = clone $this;
        $copy->path = $resource->getPath();
        $copy->overrides = $resource;

        foreach ($copy->entries as $name => $entry) {
            if ($resource->contains($name)) {
                $copy->entries[$name] = $entry->override($resource->get($name));
                $resource->remove($name);
                continue;
            }

            $copy->entries[$name] = $entry->copyTo($copy->path.'/'.$name);
        }

        foreach ($resource->listEntries() as $entry) {
            $copy->entries[$entry->getName()] = $entry;
        }

        return $copy;
    }
}
