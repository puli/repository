<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Resource;

use Puli\Repository\ResourceRepositoryInterface;
use Puli\Resource\AttachableResourceInterface;
use Puli\Resource\Collection\ResourceCollection;
use Puli\Resource\DirectoryResourceInterface;
use Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestDirectory implements DirectoryResourceInterface, AttachableResourceInterface
{
    private $path;

    private $repo;

    /**
     * @var ResourceInterface[]
     */
    private $entries = array();

    private $overrides;

    public function __construct($path = null, array $entries = array())
    {
        $this->path = $path;

        foreach ($entries as $entry) {
            $this->entries[$entry->getName()] = $entry;
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

    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        $this->path = $path;
        $this->repo = $repo;
    }

    public function detach()
    {
        $this->path = null;
        $this->repo = null;
    }

    public function override(ResourceInterface $resource)
    {
        $this->overrides = $resource;
    }

    public function getAttachedRepository()
    {
        return $this->repo;
    }

    public function getOverriddenResource()
    {
        return $this->overrides;
    }
}
