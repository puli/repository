<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Resource;

use Puli\Repository\Filesystem\FilesystemException;
use Puli\Repository\Filesystem\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\DirectoryResourceInterface;
use Puli\Repository\Resource\ResourceInterface;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\UnsupportedResourceException;

/**
 * Represents a directory on the local file system.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResource extends AbstractLocalResource implements DirectoryResourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct($localPath, $path = null, OverriddenPathLoaderInterface $pathLoader = null)
    {
        if (!is_dir($localPath)) {
            throw new FilesystemException(sprintf(
                'The path "%s" is not a directory.',
                $localPath
            ));
        }

        parent::__construct($localPath, $path, $pathLoader);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        // Use attached locator if possible
        if ($this->repo) {
            return $this->repo->get($this->repoPath.'/'.$name);
        }

        $localPath = $this->getLocalPath().'/'.$name;

        if (!file_exists($localPath)) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist.',
                $localPath
            ));
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
        if ($this->repo) {
            return $this->repo->contains($this->repoPath.'/'.$name);
        }

        return file_exists($this->getLocalPath().'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function listEntries()
    {
        // Use attached locator if possible
        if ($this->repo) {
            $entries = new LocalResourceCollection();

            foreach ($this->repo->listDirectory($this->repoPath) as $entry) {
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
    public function override(ResourceInterface $resource)
    {
        // Virtual directories may be overridden
        if ($resource instanceof DirectoryResource) {
            return;
        }

        if (!($resource instanceof DirectoryResourceInterface && $resource instanceof LocalResourceInterface)) {
            throw new UnsupportedResourceException('Can only override other local directory resources.');
        }

        parent::override($resource);
    }
}
