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
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemLocator extends AbstractResourceLocator
{
    public function __construct($rootDirectory, PatternFactoryInterface $patternFactory = null)
    {
        parent::__construct($patternFactory);

        if (!is_dir($rootDirectory)) {
            throw new \InvalidArgumentException(sprintf(
                'The path "%s" is not a directory.',
                $rootDirectory
            ));
        }

        $this->rootDirectory = rtrim(Path::canonicalize($rootDirectory), '/');
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
            return new DirectoryResource($repositoryPath, $filePath);
        }

        return new FileResource($repositoryPath, $filePath);
    }

    protected function getPatternImpl(PatternInterface $pattern)
    {
        $filePattern = $this->patternFactory->createPattern($this->rootDirectory.$pattern);
        $patternLocator = $this->patternFactory->createPatternLocator();
        $offset = strlen($this->rootDirectory) + 1;
        $results = array();

        foreach ($patternLocator->locatePaths($filePattern) as $path) {
            if (0 === strpos($path, $this->rootDirectory)) {
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

    public function getTags()
    {
        throw new \BadMethodCallException('The FilesystemLocator does not support tagging.');
    }
}
