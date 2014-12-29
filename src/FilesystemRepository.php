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
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\NoDirectoryException;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Iterator\GlobIterator;
use Puli\Repository\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Resource\Collection\FilesystemResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
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
class FilesystemRepository implements ResourceRepository
{
    /**
     * @var string
     */
    protected $baseDir;

    /**
     * Creates a new repository.
     *
     * @param string|null $baseDir The base directory of the repository on the
     *                             file system.
     */
    public function __construct($baseDir = null)
    {
        if ($baseDir) {
            Assertion::directory($baseDir);

            $this->baseDir = rtrim(Path::canonicalize($baseDir), '/');
        }
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

        $resource = is_dir($filesystemPath)
            ? new DirectoryResource($filesystemPath, $path)
            : new FileResource($filesystemPath, $path);

        $resource->attachTo($this);

        return $resource;
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
