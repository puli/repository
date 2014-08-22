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
use Webmozart\Puli\Locator\AbstractResourceLocator;
use Webmozart\Puli\Locator\DataStorageInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\LazyDirectoryResource;
use Webmozart\Puli\Resource\LazyFileResource;
use Webmozart\Puli\Resource\ResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemLocator extends AbstractResourceLocator implements DataStorageInterface
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

    protected function getImpl($repositoryPath)
    {
        $filePath = $this->rootDirectory.$repositoryPath;

        if (!file_exists($filePath)) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $repositoryPath
            ));
        }

        if (is_dir($filePath)) {
            return new LazyDirectoryResource($this, $repositoryPath, $filePath);
        }

        return new LazyFileResource($this, $repositoryPath, $filePath);
    }

    protected function getPatternImpl(PatternInterface $pattern)
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

        return new ResourceCollection($results);
    }

    protected function containsImpl($repositoryPath)
    {
        return file_exists($this->rootDirectory.$repositoryPath);
    }

    protected function containsPatternImpl(PatternInterface $pattern)
    {
        $filePattern = $this->patternFactory->createPattern($this->rootDirectory.$pattern);

        return count($this->pathFinder->findPaths($filePattern)) > 0;
    }

    public function getByTag($tag)
    {
        throw new \BadMethodCallException('The FilesystemLocator does not support tagging.');
    }

    public function getTags($repositoryPath = null)
    {
        throw new \BadMethodCallException('The FilesystemLocator does not support tagging.');
    }

    public function getAlternativePaths($repositoryPath)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectoryEntries($repositoryPath)
    {
        $repositoryPath = rtrim(Path::canonicalize($repositoryPath), '/');
        $filePath = $this->rootDirectory.$repositoryPath;
        $resources = array();

        // We can't use glob() here, because glob() doesn't list files starting
        // with "." by default
        foreach (scandir($filePath) as $name) {
            if ('.' === $name || '..' === $name) {
                continue;
            }

            $resources[] = $this->getImpl($repositoryPath.'/'.$name);
        }

        return new ResourceCollection($resources);
    }
}
