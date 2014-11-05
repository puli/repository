<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Filesystem\Resource;

use Webmozart\Puli\Filesystem\FilesystemException;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Resource\DirectoryLoaderInterface;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\Resource\UnsupportedResourceException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResource extends LocalResource implements DirectoryResourceInterface
{
    /**
     * @var ResourceInterface[]
     */
    private $entries;

    /**
     * @var AlternativePathLoaderInterface
     */
    private $entryAlternativesLoader;

    /**
     * @var DirectoryLoaderInterface
     */
    private $directoryLoader;

    public static function forPath($path, $localPath, AlternativePathLoaderInterface $alternativesLoader = null, DirectoryLoaderInterface $directoryLoader = null)
    {
        $resource = parent::forPath($path, $localPath, $alternativesLoader);
        $resource->directoryLoader = $directoryLoader;

        return $resource;
    }

    public function __construct($localPath, AlternativePathLoaderInterface $alternativesLoader = null, DirectoryLoaderInterface $directoryLoader = null)
    {
        parent::__construct($localPath, $alternativesLoader);

        if (!is_dir($localPath)) {
            throw new FilesystemException(sprintf(
                'The path "%s" is not a directory.',
                $localPath
            ));
        }

        $this->entryAlternativesLoader = $alternativesLoader;
        $this->directoryLoader = $directoryLoader;
    }

    public function add(ResourceInterface $entry)
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        $parentPath = dirname($entry->getPath());

        // Fix root directory on Windows
        if ('\\' === $parentPath) {
            $parentPath = '/';
        }

        if ($this->getPath() !== $parentPath) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot add resource "%s" to the directory "%s", since it is '.
                'located in a different directory.',
                $parentPath,
                $this->getPath()
            ));
        }

        $this->entries[$entry->getName()] = $entry;

        ksort($this->entries);
    }

    public function get($name)
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        if (!isset($this->entries[$name])) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist in directory "%s".',
                $name,
                $this->getPath()
            ));
        }

        return $this->entries[$name];
    }

    public function contains($name)
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        return isset($this->entries[$name]);
    }

    public function remove($name)
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        if (!isset($this->entries[$name])) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist in directory "%s".',
                $name,
                $this->getPath()
            ));
        }

        unset($this->entries[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function listEntries()
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        return new LocalResourceCollection($this->entries);
    }

    public function copyTo($path)
    {
        /** @var LocalDirectoryResource $copy */
        $copy = parent::copyTo($path);

        // Copy the entries if they are loaded already
        if (null !== $copy->entries) {
            $basePath = rtrim($path, '/');

            foreach ($copy->entries as $name => $entry) {
                $copy->entries[$name] = $entry->copyTo($basePath.'/'.$name);
            }
        }

        return $copy;
    }

    public function override(ResourceInterface $directory)
    {
        if (!($directory instanceof DirectoryResourceInterface && $directory instanceof LocalResourceInterface)) {
            throw new UnsupportedResourceException('Can only override other local directory resources.');
        }

        if (null === $this->entries) {
            $this->loadEntries();
        }

        /** @var LocalDirectoryResource $override */
        $override = parent::override($directory);
        $basePath = rtrim($override->getPath(), '/');

        // Override already contains the entries of $this
        // We need to update paths and link overridden entries in $directory
        foreach ($override->entries as $name => $entry) {
            if ($directory->contains($name)) {
                $override->entries[$name] = $entry->override($directory->get($name));
                $directory->remove($name);
                continue;
            }

            $override->entries[$name] = $entry->copyTo($basePath.'/'.$name);
        }

        foreach ($directory->listEntries() as $entry) {
            $name = $entry->getName();

            if (!isset($override->entries[$name])) {
                $override->entries[$name] = $entry;
            }
        }

        ksort($override->entries);

        return $override;
    }

    private function loadEntries()
    {
        $this->entries = array();

        if ($this->directoryLoader) {
            $entries = $this->directoryLoader->loadDirectoryEntries($this);

            foreach ($entries as $entry) {
                $this->entries[$entry->getName()] = $entry;
            }

            // Remove references to loaders
            $this->directoryLoader = null;
            $this->entryAlternativesLoader = null;

            return;
        }

        $localPath = $this->getLocalPath();
        $basePath = rtrim($this->getPath(), '/');
        $localBasePath = rtrim($localPath, '/');

        // We can't use glob() here, because glob() doesn't list files starting
        // with "." by default
        foreach (scandir($localPath) as $name) {
            if ('.' === $name || '..' === $name) {
                continue;
            }

            $entryPath = $localBasePath.'/'.$name;

            $this->entries[$name] = is_dir($entryPath)
                ? LocalDirectoryResource::forPath($basePath.'/'.$name, $entryPath, $this->entryAlternativesLoader)
                : LocalFileResource::forPath($basePath.'/'.$name, $entryPath, $this->entryAlternativesLoader);
        }

        // Remove reference to alternatives loader
        $this->entryAlternativesLoader = null;
    }
}
