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
use Puli\Repository\Api\Resource\BodyResource;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Iterator\GlobIterator;
use Puli\Repository\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Selector\Selector;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * A repository that copies all resources to a directory.
 *
 * This implementation is useful if you want to cache resources from a remote
 * repository on the file system.
 *
 * You need to pass the path of an existing directory to the constructor. The
 * repository will read and write resources from/to this directory.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\FileCopyRepository;
 *
 * $repo = new FileCopyRepository('/path/to/cache');
 * $repo->add('/css', new DirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * Resources passed to {@link add()} need to implement either
 * {@link BodyResource} or {@link DirectoryResource}. Other resources are not
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
 * The repository always returns instances of {@link FilesystemResource},
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
     * Creates a new repository.
     *
     * @param string             $baseDir The directory to read from and write
     *                                    to.
     * @param ResourceRepository $backend The backend repository.
     */
    public function __construct($baseDir, ResourceRepository $backend = null)
    {
        Assertion::string($baseDir);

        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        Assertion::directory($baseDir);

        parent::__construct($baseDir);

        $this->backend = $backend ?: new FilesystemRepository();
        $this->filesystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);
        $filesystemPath = $this->baseDir.$path;

        if (!file_exists($filesystemPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        return $this->createResource($filesystemPath, $path);
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
            foreach ($resource as $child) {
                $this->addResource($path.'/'.$child->getName(), $child);
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
    public function remove($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);

        Assertion::notEq('/', $query, 'The root directory cannot be removed.');

        $filesystemPaths = iterator_to_array(new GlobIterator($this->baseDir.$query));
        $removed = 0;

        foreach ($filesystemPaths as $filesystemPath) {
            // Skip paths that have already been removed
            if (!file_exists($filesystemPath)) {
                continue;
            }

            ++$removed;

            if (is_dir($filesystemPath)) {
                $removed += $this->countChildren($filesystemPath);
            }

            $this->filesystem->remove($filesystemPath);
        }

        return $removed;
    }

    private function ensureDirectoryExists($path)
    {
        $filesystemPath = $this->baseDir.$path;

        if (is_file($filesystemPath)) {
            throw NoDirectoryException::forPath($path);
        }

        if (!is_dir($filesystemPath)) {
            mkdir($filesystemPath, 0777, true);
        }
    }

    private function addResource($path, Resource $resource)
    {
        $pathInBaseDir = $this->baseDir.$path;
        $hasChildren = $resource->hasChildren();
        $hasBody = $resource instanceof BodyResource;

        if ($hasChildren && $hasBody) {
            throw new UnsupportedResourceException('Instances of BodyResource with children are not supported.');
        }

        if (file_exists($pathInBaseDir)) {
            $this->filesystem->remove($pathInBaseDir);
        }

        if ($resource instanceof FilesystemResource) {
            if ($hasBody) {
                $this->filesystem->copy($resource->getFilesystemPath(), $pathInBaseDir);
            } else {
                $this->filesystem->mirror($resource->getFilesystemPath(), $pathInBaseDir);
            }

            return;
        }

        if ($hasBody) {
            file_put_contents($pathInBaseDir, $resource->getBody());

            return;
        }

        mkdir($pathInBaseDir, 0777, true);

        foreach ($resource->listChildren() as $child) {
            $this->addResource($path.'/'.$child->getName(), $child);
        }
    }

    private function createResource($filesystemPath, $path)
    {
        $resource = is_dir($filesystemPath)
            ? new DirectoryResource($filesystemPath)
            : new FileResource($filesystemPath);

        $resource->attachTo($this, $path);

        return $resource;
    }

    private function countChildren($filesystemPath)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filesystemPath),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $iterator->rewind();
        $count = 0;

        while ($iterator->valid()) {
            $count++;
            $iterator->next();
        }

        return $count;
    }
}
