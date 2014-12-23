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

use Assert\Assertion;
use Iterator;
use Puli\Repository\Filesystem\Iterator\GlobIterator;
use Puli\Repository\Filesystem\Iterator\RecursiveDirectoryIterator;
use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\Filesystem\Resource\LocalResourceCollection;
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
        if ($rootDirectory) {
            Assertion::directory($rootDirectory);

            $this->rootDirectory = rtrim(Path::canonicalize($rootDirectory), '/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        Assertion::string($path, 'The path must be a string. Got: %2$s');
        Assertion::notEmpty($path, 'The path must not be empty.');
        Assertion::startsWith($path, '/', 'The path %s is not absolute.');

        $path = Path::canonicalize($path);
        $localPath = $this->rootDirectory.$path;

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
        Assertion::string($selector, 'The selector must be a string. Got: %2$s');
        Assertion::notEmpty($selector, 'The selector must not be empty.');
        Assertion::startsWith($selector, '/', 'The selector %s is not absolute.');

        $selector = Path::canonicalize($selector);
        $localSelector = $this->rootDirectory.$selector;

        return $this->iteratorToCollection(new GlobIterator($localSelector));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        Assertion::string($selector, 'The selector must be a string. Got: %2$s');
        Assertion::notEmpty($selector, 'The selector must not be empty.');
        Assertion::startsWith($selector, '/', 'The selector %s is not absolute.');

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
        Assertion::string($path, 'The path must be a string. Got: %2$s');
        Assertion::notEmpty($path, 'The path must not be empty.');
        Assertion::startsWith($path, '/', 'The path %s is not absolute.');

        $path = Path::canonicalize($path);
        $localPath = $this->rootDirectory.$path;

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
