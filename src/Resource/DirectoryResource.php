<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource;

use Webmozart\Puli\Resource\Collection\ResourceCollection;
use Webmozart\Puli\ResourceRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResource implements DirectoryResourceInterface, AttachableResourceInterface
{
    /**
     * @var ResourceRepositoryInterface
     */
    private $repo;

    /**
     * @var string
     */
    private $path;

    public static function createAttached(ResourceRepositoryInterface $repo, $path)
    {
        $resource = new self();
        $resource->repo = $repo;
        $resource->path = $path;

        return $resource;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return basename($this->path);
    }

    public function get($name)
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->repo->get($this->path.'/'.$name);
    }

    public function contains($name)
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->repo->contains($this->path.'/'.$name);
    }

    public function listEntries()
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        $entries = new ResourceCollection();

        foreach ($this->repo->find($this->path.'/*') as $entry) {
            $entries[$entry->getName()] = $entry;
        }

        return $entries;
    }

    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        $this->repo = $repo;
        $this->path = $path;
    }

    public function detach()
    {
        $this->repo = null;
        $this->path = null;
    }

    public function override(ResourceInterface $resource)
    {
    }
}
