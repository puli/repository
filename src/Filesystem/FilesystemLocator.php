<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Filesystem;

use Webmozart\Puli\Filesystem\PathFinder\GlobFinder;
use Webmozart\Puli\Filesystem\PathFinder\PathFinderInterface;
use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Filesystem\Resource\LocalFileResource;
use Webmozart\Puli\Filesystem\Resource\LocalResourceCollection;
use Webmozart\Puli\Locator\AbstractResourceLocator;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemLocator extends AbstractResourceLocator
{
    /**
     * @var string
     */
    private $rootDirectory = '';

    /**
     * @var PathFinderInterface
     */
    private $pathFinder;

    public function __construct($rootDirectory = null, PatternFactoryInterface $patternFactory = null, PathFinderInterface $pathFinder = null)
    {
        parent::__construct($patternFactory);

        if ($rootDirectory && !is_dir($rootDirectory)) {
            throw new \InvalidArgumentException(sprintf(
                'The path "%s" is not a directory.',
                $rootDirectory
            ));
        }

        if ($rootDirectory) {
            $this->rootDirectory = rtrim(Path::canonicalize($rootDirectory), '/');
        }

        $this->pathFinder = $pathFinder ?: new GlobFinder();
    }

    public function getByTag($tag)
    {
        throw new \BadMethodCallException('The FilesystemLocator does not support tagging.');
    }

    public function getTags($path = null)
    {
        throw new \BadMethodCallException('The FilesystemLocator does not support tagging.');
    }

    protected function getImpl($path)
    {
        $localPath = $this->rootDirectory.$path;

        if (!file_exists($localPath)) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $path
            ));
        }

        return is_dir($localPath)
            ? LocalDirectoryResource::forPath($path, $localPath)
            : LocalFileResource::forPath($path, $localPath);
    }

    protected function findImpl(PatternInterface $pattern)
    {
        $filePattern = $this->patternFactory->createPattern($this->rootDirectory.$pattern);
        $offset = strlen($this->rootDirectory) + 1;
        $results = array();

        foreach ($this->pathFinder->findPaths($filePattern) as $path) {
            if ('' !== $this->rootDirectory && 0 === strpos($path, $this->rootDirectory)) {
                $path = '/'.substr($path, $offset);
            }

            $results[] = $this->getImpl($path);
        }

        return new LocalResourceCollection($results);
    }

    protected function containsImpl($path)
    {
        return file_exists($this->rootDirectory.$path);
    }

    protected function containsPatternImpl(PatternInterface $pattern)
    {
        $filePattern = $this->patternFactory->createPattern($this->rootDirectory.$pattern);

        return count($this->pathFinder->findPaths($filePattern)) > 0;
    }
}
