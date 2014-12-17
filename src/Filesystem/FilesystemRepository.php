<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem;

use InvalidArgumentException;
use Iterator;
use Puli\Repository\Filesystem\Iterator\GlobIterator;
use Puli\Repository\Filesystem\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\Filesystem\Resource\LocalResourceCollection;
use Puli\Repository\InvalidPathException;
use Puli\Repository\NoDirectoryException;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\ResourceRepository;
use Webmozart\PathUtil\Path;

/**
 * A repository reading from the local file system.
 *
 * Resources can be read using their absolute file system paths:
 *
 * ```php
 * use Puli\Repository\Filesystem\FilesystemRepository;
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
    private $rootDirectory = '';

    /**
     * Creates a new repository.
     *
     * @param string|null $rootDirectory The root directory of the repository
     *                                   on the local file system.
     */
    public function __construct($rootDirectory = null)
    {
        if ($rootDirectory && !is_dir($rootDirectory)) {
            throw new InvalidArgumentException(sprintf(
                'The path "%s" is not a directory.',
                $rootDirectory
            ));
        }

        if ($rootDirectory) {
            $this->rootDirectory = rtrim(Path::canonicalize($rootDirectory), '/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        if ('' === $path) {
            throw new InvalidPathException('The path must not be empty.');
        }

        if (!is_string($path)) {
            throw new InvalidPathException(sprintf(
                'The path must be a string. Is: %s.',
                is_object($path) ? get_class($path) : gettype($path)
            ));
        }

        if ('/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $path
            ));
        }

        $path = Path::canonicalize($path);
        $localPath = $this->rootDirectory.$path;

        if (!file_exists($localPath)) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist.',
                $path
            ));
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
        if ('' === $selector) {
            throw new InvalidPathException('The selector must not be empty.');
        }

        if (!is_string($selector)) {
            throw new InvalidPathException(sprintf(
                'The selector must be a string. Is: %s.',
                is_object($selector) ? get_class($selector) : gettype($selector)
            ));
        }

        if ('/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The selector "%s" is not absolute.',
                $selector
            ));
        }

        $selector = Path::canonicalize($selector);
        $localSelector = $this->rootDirectory.$selector;

        return $this->iteratorToCollection(new GlobIterator($localSelector));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        if ('' === $selector) {
            throw new InvalidPathException('The selector must not be empty.');
        }

        if (!is_string($selector)) {
            throw new InvalidPathException(sprintf(
                'The selector must be a string. Is: %s.',
                is_object($selector) ? get_class($selector) : gettype($selector)
            ));
        }

        if ('/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The selector "%s" is not absolute.',
                $selector
            ));
        }

        $selector = Path::canonicalize($selector);
        $iterator = new GlobIterator($this->rootDirectory.$selector);
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function listDirectory($path)
    {
        if ('' === $path) {
            throw new InvalidPathException('The path must not be empty.');
        }

        if (!is_string($path)) {
            throw new InvalidPathException(sprintf(
                'The path must be a string. Is: %s.',
                is_object($path) ? get_class($path) : gettype($path)
            ));
        }

        if ('/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $path
            ));
        }

        $path = Path::canonicalize($path);
        $localPath = $this->rootDirectory.$path;

        if (!file_exists($localPath)) {
            throw new ResourceNotFoundException(sprintf(
                'The directory "%s" does not exist.',
                $path
            ));
        }

        if (!is_dir($localPath)) {
            throw new NoDirectoryException(sprintf(
                'The path "%s" is not a directory.',
                $path
            ));
        }

        return $this->iteratorToCollection(new RecursiveDirectoryIterator($localPath));
    }

    private function iteratorToCollection(Iterator $iterator)
    {
        $offset = strlen($this->rootDirectory);
        $resources = array();

        foreach ($iterator as $localPath) {
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
