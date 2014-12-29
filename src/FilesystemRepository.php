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

use Iterator;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\BodyResource;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Resource\Collection\FilesystemResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobIterator;
use Webmozart\Glob\Iterator\RecursiveDirectoryIterator;
use Webmozart\PathUtil\Path;

/**
 * A repository reading from the file system.
 *
 * Resources can be read using their absolute file system paths:
 *
 * ```php
 * use Puli\Repository\FilesystemRepository;
 *
 * $repo = new FilesystemRepository();
 * $resource = $repo->get('/home/puli/.gitconfig');
 * ```
 *
 * The returned resources implement {@link FilesystemResource}.
 *
 * Optionally, a root directory can be passed to the constructor. Then all paths
 * will be read relative to that directory:
 *
 * ```php
 * $repo = new FilesystemRepository('/home/puli');
 * $resource = $repo->get('/.gitconfig');
 * ```
 *
 * While "." and ".." segments are supported, files outside the root directory
 * cannot be read. Any leading ".." segments will simply be stripped off.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepository implements EditableRepository
{
    /**
     * @var string
     */
    protected $baseDir;

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
     * @param string $baseDir The base directory of the repository on the file
     *                        system.
     */
    public function __construct($baseDir = '/', ResourceRepository $backend = null)
    {
        Assertion::directory($baseDir);

        $this->baseDir = rtrim(Path::canonicalize($baseDir), '/');
        $this->backend = $backend;
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
    public function find($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);
        $glob = $this->baseDir.$query;

        return $this->iteratorToCollection(new GlobIterator($glob));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);
        $iterator = new GlobIterator($this->baseDir.$query);
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);
        $filesystemPath = $this->baseDir.$path;

        if (!file_exists($filesystemPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        if (!is_dir($filesystemPath)) {
            throw NoDirectoryException::forPath($path);
        }

        $iterator = new RecursiveDirectoryIterator($filesystemPath);
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);
        $filesystemPath = $this->baseDir.$path;

        if (!file_exists($filesystemPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        if (!is_dir($filesystemPath)) {
            throw NoDirectoryException::forPath($path);
        }

        return $this->iteratorToCollection(new RecursiveDirectoryIterator($filesystemPath));
    }

    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (is_string($resource)) {
            if (false !== strpos($resource, '*')) {
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

        $filesystemPaths = Glob::glob($this->baseDir.$query);
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

    private function iteratorToCollection(Iterator $iterator)
    {
        $offset = strlen($this->baseDir);
        $filesystemPaths = iterator_to_array($iterator);
        $resources = array();

        // RecursiveDirectoryIterator is not guaranteed to return sorted results
        sort($filesystemPaths);

        foreach ($filesystemPaths as $filesystemPath) {
            $path = substr($filesystemPath, $offset);

            $resource = is_dir($filesystemPath)
                ? new DirectoryResource($filesystemPath, $path)
                : new FileResource($filesystemPath, $path);

            $resource->attachTo($this);

            $resources[] = $resource;
        }

        return new FilesystemResourceCollection($resources);
    }
}
