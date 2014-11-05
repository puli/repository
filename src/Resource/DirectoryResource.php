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

use Webmozart\Puli\Locator\ResourceNotFoundException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResource implements DirectoryResourceInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var ResourceInterface[]|null
     */
    private $entries;

    /**
     * @var DirectoryLoaderInterface
     */
    private $directoryLoader;

    public static function forPath($path, DirectoryLoaderInterface $directoryLoader = null)
    {
        $resource = new self($directoryLoader);
        $resource->path = $path;

        return $resource;
    }

    public function __construct(DirectoryLoaderInterface $directoryLoader = null)
    {
        $this->directoryLoader = $directoryLoader;
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

    public function copyTo($path)
    {
        $copy = clone $this;
        $copy->path = $path;

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
        if (!$directory instanceof self) {
            throw new UnsupportedResourceException('Expected a RealDirectoryResource instance.');
        }

        if (null === $this->entries) {
            $this->loadEntries();
        }

        $override = clone $directory;
        $basePath = rtrim($override->path, '/');

        // Override already contains the entries of $directory
        // We need to add the entries of this instance yet
        foreach ($this->entries as $name => $entry) {
            // Override existing entries in $directory
            if (isset($override->entries[$name])) {
                $override->entries[$name] = $entry->override($override->entries[$name]);
                continue;
            }

            // Copy other entries of the current directory to the correct path
            $override->entries[$name] = $entry->copyTo($basePath.'/'.$name);
        }

        ksort($override->entries);

        return $override;
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

        return new ResourceCollection($this->entries);
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

            return;
        }
    }
}
