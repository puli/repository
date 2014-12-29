<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\NoDirectoryException;
use Puli\Repository\Api\Resource\DirectoryResource;
use Puli\Repository\Api\Resource\FileResource;
use Puli\Repository\Api\Resource\LocalResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Iterator\GlobIterator;
use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\LocalFileResource;
use Puli\Repository\Selector\Selector;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\PathUtil\Path;

/**
 * A repository that copies all resources to a directory.
 *
 * This implementation is useful if you want to cache resources from a remote
 * repository on the local file system.
 *
 * You need to pass the path of an existing directory to the constructor. The
 * repository will read and write resources from/to this directory.
 *
 * Additionally, you need to pass a key-value store that stores the versioning
 * information of the resources. If you don't need versioning, pass a NullStore
 * instead.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\FileCopyRepository;
 *
 * $repo = new FileCopyRepository('/path/to/cache');
 * $repo->add('/css', new LocalDirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * Resources passed to {@link add()} need to implement either
 * {@link FileResource} or {@link DirectoryResource}. Other resources are not
 * supported.
 *
 * Alternatively, another repository can be passed as "backend". The paths of
 * this backend can be passed to the second argument of {@link add()}. By
 * default, a {@link FilesystemRepository} is used:
 *
 * ```php
 * use Puli\Repository\FileCopyRepository;
 *
 * $repo = new FileCopyRepository('/path/to/cache');
 * $repo->add('/css', '/path/to/project/res/css');
 * ```
 *
 * You can also create the backend manually and pass it to the constructor:
 *
 * ```php
 * use Puli\Repository\FileCopyRepository;
 * use Puli\Repository\FilesystemRepository;
 *
 * $backend = new FilesystemRepository('/path/to/project');
 *
 * $repo = new FileCopyRepository('/path/to/cache', $backend)
 * $repo->add('/css', '/res/css');
 * ```
 *
 * The repository always returns instances of {@link LocalResource},
 * regardless of the type of resource you passed to {@link add()}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileCopyRepository extends FilesystemRepository implements EditableRepository
{
    /**
     * @var ResourceRepository
     */
    private $backend;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var KeyValueStore
     */
    private $versionStore;

    /**
     * Creates a new repository.
     *
     * @param string             $baseDir      The directory to read from and
     *                                         write to.
     * @param KeyValueStore      $versionStore The store for storing the
     *                                         resource versions.
     * @param ResourceRepository $backend      The backend repository.
     */
    public function __construct($baseDir, KeyValueStore $versionStore, ResourceRepository $backend = null)
    {
        Assertion::string($baseDir);

        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        Assertion::directory($baseDir);

        parent::__construct($baseDir);

        $this->backend = $backend ?: new FilesystemRepository();
        $this->filesystem = new Filesystem();
        $this->versionStore = $versionStore;
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, $version = null)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);
        $localPath = $this->baseDir.$path;

        if (!file_exists($localPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        $versions = $this->versionStore->get($path, array(1 => $path));
        $latestVersion = count($versions);

        if (null === $version || $latestVersion === $version) {
            return $this->createResource($localPath, $path, $latestVersion);
        }

        if (!isset($versions[$version])) {
            throw ResourceNotFoundException::forVersion($version, $path);
        }

        $localPath = Path::makeAbsolute($versions[$version], $this->baseDir);

        return $this->createResource($localPath, $path, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (is_string($resource)) {
            if (Selector::isSelector($resource)) {
                $resource = $this->backend->find($resource);
            } else {
                $resource = $this->backend->get($resource);
            }
        }

        if ($resource instanceof ResourceCollection) {
            $this->ensureDirectoryExists($path);
            foreach ($resource as $entry) {
                $this->addResource($path.'/'.$entry->getName(), $entry);
            }

            return;
        }

        if ($resource instanceof Resource) {
            $this->ensureDirectoryExists(Path::getDirectory($path));
            $this->addResource($path, $resource);

            return;
        }

        throw new UnsupportedResourceException(sprintf(
            'The passed resource must be a string, Resource or '.
            'ResourceCollection. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($selector)
    {
        Assertion::selector($selector);

        $selector = Path::canonicalize($selector);

        Assertion::notEq('/', $selector, 'The root directory cannot be removed.');

        $localPaths = iterator_to_array(new GlobIterator($this->baseDir.$selector));
        $removed = 0;

        foreach ($localPaths as $localPath) {
            // Skip paths that have already been removed
            if (!file_exists($localPath)) {
                continue;
            }

            ++$removed;

            if (is_dir($localPath)) {
                $resource = new LocalDirectoryResource($localPath);
                $removed += $resource->count(true);
            }

            $this->filesystem->remove($localPath);
        }

        return $removed;
    }

    private function ensureDirectoryExists($path)
    {
        $localPath = $this->baseDir.$path;

        if (is_file($localPath)) {
            throw NoDirectoryException::forPath($path);
        }

        if (!is_dir($localPath)) {
            mkdir($localPath, 0777, true);
        }
    }

    private function addResource($path, Resource $resource)
    {
        $pathInBaseDir = $this->baseDir.$path;
        $isDir = $resource instanceof DirectoryResource;
        $isFile = $resource instanceof FileResource;

        if (!$isDir && !$isFile) {
            throw new UnsupportedResourceException(sprintf(
                'Added resources must implement FileResource or '.
                'DirectoryResource. Got: %s',
                is_object($resource) ? get_class($resource) : gettype($resource)
            ));
        }

        // Remove previous versions
        if (file_exists($pathInBaseDir)) {
            $this->filesystem->remove($pathInBaseDir);
        }

        if ($resource instanceof LocalResource) {
            if ($isDir) {
                $this->filesystem->mirror($resource->getLocalPath(), $pathInBaseDir);
            } else {
                $this->filesystem->copy($resource->getLocalPath(), $pathInBaseDir);
            }

            $versions = $this->versionStore->get($path, array());
            $version = count($versions) + 1;
            $versions[$version] = Path::makeRelative($resource->getLocalPath(), $this->baseDir);
            $this->versionStore->set($path, $versions);

            return;
        }

        // Versioning is not supported for non-local resources
        // We always store one version only for such resources
        $this->versionStore->set($path, array($path));

        if ($isDir) {
            mkdir($pathInBaseDir, 0777, true);

            foreach ($resource->listEntries() as $entry) {
                $this->addResource($path.'/'.$entry->getName(), $entry);
            }

            return;
        }

        file_put_contents($pathInBaseDir, $resource->getContents());
    }

    private function createResource($localPath, $path, $version)
    {
        $resource = is_dir($localPath)
            ? new LocalDirectoryResource($localPath)
            : new LocalFileResource($localPath);

        $resource->attachTo($this, $path, $version);

        return $resource;
    }
}
