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

use Webmozart\Puli\LocatorDumper\PhpResourceLocatorDumper;
use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\PatternLocator\PatternFactoryInterface;
use Webmozart\Puli\Resource\LazyDirectoryResource;
use Webmozart\Puli\Resource\LazyFileResource;
use Webmozart\Puli\Resource\LazyResourceCollection;
use Webmozart\Puli\Tag\LazyTag;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpResourceLocator extends AbstractResourceLocator implements DataStorageInterface
{
    private $cacheDir;

    private $resources = array();

    private $filePaths;

    private $dirPaths;

    private $alternativePaths;

    private $tags;

    public function __construct($cacheDir, PatternFactoryInterface $patternFactory = null)
    {
        if (!file_exists($cacheDir.'/'.PhpResourceLocatorDumper::FILE_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.PhpResourceLocatorDumper::DIR_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.PhpResourceLocatorDumper::ALTERNATIVE_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.PhpResourceLocatorDumper::TAGS_FILE)) {
            throw new \InvalidArgumentException(sprintf(
                'The dump at "%s" is invalid. Please try to recreate it.',
                $cacheDir
            ));
        }

        parent::__construct($patternFactory);

        $this->cacheDir = $cacheDir;
    }

    public function getByTag($tag)
    {
        if (null === $this->tags) {
            $this->tags = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::TAGS_FILE);
        }

        if (!isset($this->tags[$tag])) {
            return array();
        }

        if (!$this->tags[$tag] instanceof LazyTag) {
            return iterator_to_array($this->createTag($tag));
        }

        return iterator_to_array($this->tags[$tag]);
    }

    /**
     * @return \Webmozart\Puli\Tag\TagInterface[]
     */
    public function getTags($repositoryPath = null)
    {
        if (null === $this->tags) {
            $this->tags = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::TAGS_FILE);
        }

        foreach ($this->tags as $tag => $resources) {
            if (!$resources instanceof LazyTag) {
                $this->createTag($tag);
            }
        }

        // Hide the internal keys from the outside
        return array_values($this->tags);
    }

    public function getAlternativePaths($repositoryPath)
    {
        if (null === $this->alternativePaths) {
            $this->alternativePaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::ALTERNATIVE_PATHS_FILE);
        }

        if (isset($this->alternativePaths[$repositoryPath])) {
            return $this->alternativePaths[$repositoryPath];
        }

        return array();
    }

    public function getDirectoryEntries($repositoryPath)
    {
        return $this->getPatternImpl(new GlobPattern(rtrim($repositoryPath, '/').'/*'));
    }

    protected function getImpl($repositoryPath)
    {
        // Return the resource if it was already loaded
        if (isset($this->resources[$repositoryPath])) {
            return $this->resources[$repositoryPath];
        }

        // Load the mapping of repository paths to file paths if needed
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::FILE_PATHS_FILE);
        }

        // Create LazyFileResource instances for files
        if (array_key_exists($repositoryPath, $this->filePaths)) {
            return $this->createFile($repositoryPath);
        }

        // Load the mapping of repository paths to directory paths if needed
        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::DIR_PATHS_FILE);
        }

        // Create LazyDirectoryResource instances for directories
        if (array_key_exists($repositoryPath, $this->dirPaths)) {
            return $this->createDirectory($repositoryPath);
        }

        throw new ResourceNotFoundException(sprintf(
            'The resource "%s" was not found.',
            $repositoryPath
        ));
    }

    protected function getPatternImpl(PatternInterface $pattern)
    {
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::DIR_PATHS_FILE);
        }

        $resources = array();
        $staticPrefix = $pattern->getStaticPrefix();
        $regExp = $pattern->getRegularExpression();

        foreach ($this->resources as $repositoryPath => $resource) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($repositoryPath, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $repositoryPath)) {
                continue;
            }

            $resources[$repositoryPath] = $resource;
        }

        foreach ($this->filePaths as $repositoryPath => $path) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($repositoryPath, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $repositoryPath)) {
                continue;
            }

            $resources[$repositoryPath] = $this->createFile($repositoryPath);
        }

        foreach ($this->dirPaths as $repositoryPath => $path) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($repositoryPath, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $repositoryPath)) {
                continue;
            }

            $resources[$repositoryPath] = $this->createDirectory($repositoryPath);
        }

        ksort($resources);

        // Hide the keys of this implementation from accessing code
        return array_values($resources);
    }

    protected function containsImpl($repositoryPath)
    {
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::DIR_PATHS_FILE);
        }

        return isset($this->resources[$repositoryPath])
            // The path may be NULL, so use array_key_exists()
            || array_key_exists($repositoryPath, $this->filePaths)
            || array_key_exists($repositoryPath, $this->dirPaths);
    }

    protected function containsPatternImpl(PatternInterface $pattern)
    {
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.PhpResourceLocatorDumper::DIR_PATHS_FILE);
        }

        $staticPrefix = $pattern->getStaticPrefix();
        $regExp = $pattern->getRegularExpression();

        foreach ($this->filePaths as $path => $resource) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($path, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $path)) {
                continue;
            }

            return true;
        }

        foreach ($this->dirPaths as $path => $resource) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($path, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $path)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function createFile($repositoryPath)
    {
        $this->resources[$repositoryPath] = new LazyFileResource(
            $this,
            $repositoryPath,
            $this->filePaths[$repositoryPath]
        );

        // Remove to reduce number of loops in future calls
        unset($this->filePaths[$repositoryPath]);

        // Maintain order of resources
        ksort($this->resources);

        return $this->resources[$repositoryPath];
    }

    private function createDirectory($repositoryPath)
    {
        $this->resources[$repositoryPath] = new LazyDirectoryResource(
            $this,
            $repositoryPath,
            $this->dirPaths[$repositoryPath]
        );

        // Remove to reduce number of loops in future calls
        unset($this->dirPaths[$repositoryPath]);

        // Maintain order of resources
        ksort($this->resources);

        return $this->resources[$repositoryPath];
    }

    private function createTag($tag)
    {
        $this->tags[$tag] = new LazyTag(
            $this,
            $tag,
            new LazyResourceCollection($this, $this->tags[$tag])
        );

        return $this->tags[$tag];
    }
}
