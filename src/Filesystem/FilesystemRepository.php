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
use Puli\Util\Path;
use Puli\Util\Selector;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepository implements ResourceRepositoryInterface
{
    /**
     * @var string
     */
    private $rootDirectory = '';

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

    public function get($path)
    {
        if (isset($path[0]) && '/' !== $path[0]) {
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

    public function find($selector)
    {
        if (isset($selector[0]) && '/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
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

    public function contains($selector)
    {
        if (isset($selector[0]) && '/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $selector
            ));
        }

        $selector = Path::canonicalize($selector);
        $glob = Selector::toGlob($this->rootDirectory.$selector);

        return count(glob($glob, GLOB_BRACE)) > 0;
    }

    public function getByTag($tag)
    {
        return new ResourceCollection();
    }

    public function getTags($path = null)
    {
        return array();
    }
}
