<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

use Puli\Repository\Api\Resource\DetachedException;
use Puli\Repository\Api\Resource\DirectoryResource;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * An in-memory directory in the repository.
 *
 * This class is mostly used for repository directories that are created on
 * demand:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/webmozart/puli/file', $resource);
 *
 * // implies:
 * $repo->add('/', new VirtualDirectoryResource());
 * $repo->add('/webmozart', new VirtualDirectoryResource());
 * $repo->add('/webmozart/puli', new VirtualDirectoryResource());
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VirtualDirectoryResource extends AbstractResource implements DirectoryResource
{
    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->getRepository()) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->getRepository()->get($this->getRepositoryPath().'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($name)
    {
        if (!$this->getRepository()) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->getRepository()->contains($this->getRepositoryPath().'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function listEntries()
    {
        if (!$this->getRepository()) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        $entries = new ArrayResourceCollection();

        foreach ($this->getRepository()->listDirectory($this->getRepositoryPath()) as $entry) {
            $entries[$entry->getName()] = $entry;
        }

        return $entries;
    }

    /**
     * {@inheritdoc}
     */
    public function count($deep = false)
    {
        if (!$this->getRepository()) {
            throw new DetachedException('Cannot count entries of a detached directory.');
        }

        $entries = $this->getRepository()->listDirectory($this->getRepositoryPath());
        $count = count($entries);

        if ($deep) {
            foreach ($entries as $entry) {
                if ($entry instanceof DirectoryResource) {
                    $count += $entry->count(true);
                }
            }
        }

        return $count;
    }
}
