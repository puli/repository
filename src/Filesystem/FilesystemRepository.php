<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Filesystem;

use Puli\Filesystem\Resource\LocalDirectoryResource;
use Puli\Filesystem\Resource\LocalFileResource;
use Puli\Filesystem\Resource\LocalResourceCollection;
use Puli\Repository\InvalidPathException;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Resource\Collection\ResourceCollection;
use Puli\Util\Selector;
use Webmozart\PathUtil\Path;

/**
 * A repository reading from the local file system.
 *
 * Resources can be read using their absolute file system paths:
 *
 * ```php
 * use Puli\Filesystem\FilesystemRepository;
 *
 * $repo = new FilesystemRepository();
 * $resource = $repo->get('/home/puli/.gitconfig');
 * ```
 *
 * The returned resources implement {@link LocalResourceInterface}.
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
class FilesystemRepository implements ResourceRepositoryInterface
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
            throw new \InvalidArgumentException(sprintf(
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
                $localPath
            ));
        }

        return is_dir($localPath)
            ? LocalDirectoryResource::createAttached($this, $path, $localPath)
            : LocalFileResource::createAttached($this, $path, $localPath);
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
        $glob = Selector::toGlob($this->rootDirectory.$selector);
        $offset = strlen($this->rootDirectory);
        $resources = array();

        foreach (glob($glob, GLOB_BRACE) as $localPath) {
            if ('/.' === substr($localPath, -2) || '/..' === substr($localPath, -3)) {
                continue;
            }

            $resources[] = is_dir($localPath)
                ? LocalDirectoryResource::createAttached($this, substr($localPath, $offset), $localPath)
                : LocalFileResource::createAttached($this, substr($localPath, $offset), $localPath);
        }

        return new LocalResourceCollection($resources);
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
        $glob = Selector::toGlob($this->rootDirectory.$selector);

        return count(glob($glob, GLOB_BRACE)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function findByTag($tag)
    {
        return new ResourceCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getTags($path = null)
    {
        return array();
    }
}
