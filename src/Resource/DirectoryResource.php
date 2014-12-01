<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Resource;

use Puli\Repository\ResourceRepositoryInterface;
use Puli\Resource\Collection\ResourceCollection;

/**
 * An in-memory directory in the repository.
 *
 * This class is mostly used for repository directories that are created on
 * demand:
 *
 * ```php
 * use Puli\Repository\ResourceRepository;
 *
 * $repo = new ResourceRepository();
 * $repo->add('/webmozart/puli/file', $resource);
 *
 * // implies:
 * $repo->add('/', new DirectoryResource());
 * $repo->add('/webmozart', new DirectoryResource());
 * $repo->add('/webmozart/puli', new DirectoryResource());
 * ```
 *
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

    /**
     * Creates a directory that is already attached to a repository.
     *
     * @param ResourceRepositoryInterface $repo The repository.
     * @param string                      $path The path in the repository.
     *
     * @return DirectoryResource The created resource.
     */
    public static function createAttached(ResourceRepositoryInterface $repo, $path)
    {
        $resource = new static();
        $resource->repo = $repo;
        $resource->path = $path;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->path ? basename($this->path) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->repo->get($this->path.'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($name)
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->repo->contains($this->path.'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        $this->repo = $repo;
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $this->repo = null;
        $this->path = null;
    }

    /**
     * Does nothing.
     *
     * @param ResourceInterface $resource The overridden resource.
     */
    public function override(ResourceInterface $resource)
    {
    }
}
