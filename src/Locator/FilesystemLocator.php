<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Locator;

use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\LazyDirectoryResource;
use Webmozart\Puli\Resource\LazyFileResource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemLocator extends AbstractResourceLocator implements DataStorageInterface
{
    private $rootDirectory = '';

    public function __construct($rootDirectory = null, PatternFactoryInterface $patternFactory = null)
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
        $patternLocator = $this->patternFactory->createPatternLocator();
        $offset = strlen($this->rootDirectory) + 1;
        $results = array();

        foreach ($patternLocator->locatePaths($filePattern) as $path) {
            if ('' !== $this->rootDirectory && 0 === strpos($path, $this->rootDirectory)) {
                $path = '/'.substr($path, $offset);
            }

            $results[] = $this->getImpl($path);
        }

        return $results;
    }

    protected function containsImpl($repositoryPath)
    {
        return file_exists($this->rootDirectory.$repositoryPath);
    }

    protected function containsPatternImpl(PatternInterface $pattern)
    {
        $filePattern = $this->patternFactory->createPattern($this->rootDirectory.$pattern);
        $patternLocator = $this->patternFactory->createPatternLocator();

        return count($patternLocator->locatePaths($filePattern)) > 0;
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
     * @param $repositoryPath
     *
     * @return \Webmozart\Puli\Resource\ResourceInterface[]
     */
    public function getDirectoryEntries($repositoryPath)
    {
        $repositoryPath = rtrim(Path::canonicalize($repositoryPath), '/');
        $filePath = $this->rootDirectory.$repositoryPath;
        $results = array();

        // We can't use glob() here, because glob() doesn't list files starting
        // with "." by default
        foreach (scandir($filePath) as $name) {
            if ('.' === $name || '..' === $name) {
                continue;
            }

            $results[] = $this->getImpl($repositoryPath.'/'.$name);
        }

        return $results;
    }
}
