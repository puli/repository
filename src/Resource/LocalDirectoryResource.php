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

use Assert\Assertion;
use Puli\Repository\Api\Resource\DirectoryResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Resource\Collection\LocalResourceCollection;

/**
 * Represents a directory on the local file system.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResource extends AbstractLocalResource implements DirectoryResource
{
    /**
     * {@inheritdoc}
     */
    public function __construct($localPath, $path = null)
    {
        Assertion::directory($localPath);

        parent::__construct($localPath, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        // Use attached locator if possible
        if ($this->getRepository()) {
            return $this->getRepository()->get($this->getRepositoryPath().'/'.$name);
        }

        $localPath = $this->getLocalPath().'/'.$name;

        if (!file_exists($localPath)) {
            throw ResourceNotFoundException::forPath($this->getPath().'/'.$name);
        }

        return is_dir($localPath)
            ? new LocalDirectoryResource($localPath)
            : new LocalFileResource($localPath);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($name)
    {
        // Use attached locator if possible
        if ($this->getRepository()) {
            return $this->getRepository()->contains($this->getRepositoryPath().'/'.$name);
        }

        return file_exists($this->getLocalPath().'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function listEntries()
    {
        // Use attached locator if possible
        if ($this->getRepository()) {
            $entries = new LocalResourceCollection();

            foreach ($this->getRepository()->listDirectory($this->getRepositoryPath()) as $entry) {
                $entries[$entry->getName()] = $entry;
            }

            return $entries;
        }

        $localPath = $this->getLocalPath();
        $iterator = new RecursiveDirectoryIterator($localPath, RecursiveDirectoryIterator::CURRENT_AS_FILE);
        $entries = array();

        // We can't use glob() here, because glob() doesn't list files starting
        // with "." by default
        foreach ($iterator as $path => $name) {
            $entries[$name] = is_dir($path)
                ? new LocalDirectoryResource($path)
                : new LocalFileResource($path);
        }

        return new LocalResourceCollection($entries);
    }

    /**
     * {@inheritdoc}
     */
    public function count($deep = false)
    {
        $entries = $this->listEntries();
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
