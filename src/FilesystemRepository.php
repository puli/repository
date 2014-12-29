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
use Puli\Repository\Api\NoDirectoryException;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Iterator\GlobIterator;
use Puli\Repository\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Resource\Collection\LocalResourceCollection;
use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\LocalFileResource;
use Webmozart\PathUtil\Path;

/**
 * A repository reading from the local file system.
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
 * The returned resources implement {@link LocalResource}.
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
     *                             local file system.
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
        $localPath = $this->baseDir.$path;

        if (!file_exists($localPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        $resource = is_dir($localPath)
            ? new LocalDirectoryResource($localPath, $path)
            : new LocalFileResource($localPath, $path);

        $resource->attachTo($this);

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function find($selector)
    {
        Assertion::selector($selector);

        $selector = Path::canonicalize($selector);
        $localSelector = $this->baseDir.$selector;

        return $this->iteratorToCollection(new GlobIterator($localSelector));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        Assertion::selector($selector);

        $selector = Path::canonicalize($selector);
        $iterator = new GlobIterator($this->baseDir.$selector);
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function listDirectory($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);
        $localPath = $this->baseDir.$path;

        if (!file_exists($localPath)) {
            throw ResourceNotFoundException::forPath($path);
        }

        if (!is_dir($localPath)) {
            throw NoDirectoryException::forPath($path);
        }

        return $this->iteratorToCollection(new RecursiveDirectoryIterator($localPath));
    }

    private function iteratorToCollection(Iterator $iterator)
    {
        $offset = strlen($this->baseDir);
        $localPaths = iterator_to_array($iterator);
        $resources = array();

        // RecursiveDirectoryIterator is not guaranteed to return sorted results
        sort($localPaths);

        foreach ($localPaths as $localPath) {
            $path = substr($localPath, $offset);

            $resource = is_dir($localPath)
                ? new LocalDirectoryResource($localPath, $path)
                : new LocalFileResource($localPath, $path);

            $resource->attachTo($this);

            $resources[] = $resource;
        }

        return new LocalResourceCollection($resources);
    }
}
