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
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Resource\Collection\FilesystemResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Creates a new repository.
     *
     * @param string $baseDir The base directory of the repository on the file
     *                        system.
     */
    public function __construct($baseDir = '/')
    {
        Assertion::directory($baseDir);

        $this->baseDir = rtrim(Path::canonicalize($baseDir), '/');
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
        return $this->iteratorToCollection($this->getGlobIterator($query, $language));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        $iterator = $this->getGlobIterator($query, $language);
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        $filesystemPath = $this->getFilesystemPath($path);

        if (!is_dir($filesystemPath)) {
            return false;
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
        $filesystemPath = $this->getFilesystemPath($path);

        if (!is_dir($filesystemPath)) {
            return new FilesystemResourceCollection();
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
            'The passed resource must be a Resource or ResourceCollection. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        $iterator = $this->getGlobIterator($query, $language);
        $removed = 0;

        Assertion::notEq('', trim($query, '/'), 'The root directory cannot be removed.');

        // There's some problem with concurrent deletions at the moment
        foreach (iterator_to_array($iterator) as $filesystemPath) {
            $this->removeResource($filesystemPath, $removed);
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $iterator = new RecursiveDirectoryIterator($this->baseDir);
        $removed = 0;

        foreach ($iterator as $filesystemPath) {
            $this->removeResource($filesystemPath, $removed);
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

    private function removeResource($filesystemPath, &$removed)
    {
        // Skip paths that have already been removed
        if (!file_exists($filesystemPath)) {
            return;
        }

        ++$removed;

        if (is_dir($filesystemPath)) {
            $removed += $this->countChildren($filesystemPath);
        }

        $this->filesystem->remove($filesystemPath);
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

    private function getFilesystemPath($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);
        $filesystemPath = $this->baseDir.$path;

        if (!file_exists($filesystemPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        return $filesystemPath;
    }

    private function getGlobIterator($query, $language)
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);

        return new GlobIterator($this->baseDir.$query);
    }
}
