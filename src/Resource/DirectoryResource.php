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

use Puli\Repository\Resource\Collection\ResourceCollection;

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
class DirectoryResource extends AbstractResource implements DirectoryResourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->repo->get($this->repoPath.'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($name)
    {
        if (!$this->repo) {
            throw new DetachedException('Cannot read files from a detached directory.');
        }

        return $this->repo->contains($this->repoPath.'/'.$name);
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

        foreach ($this->repo->listDirectory($this->repoPath) as $entry) {
            $entries[$entry->getName()] = $entry;
        }

        return $entries;
    }
}
