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

use Webmozart\Puli\Filesystem\Resource\AlternativePathLoaderInterface;
use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Filesystem\Resource\LocalFileResource;
use Webmozart\Puli\Filesystem\Resource\LocalResourceCollection;
use Webmozart\Puli\Filesystem\Resource\LocalResourceInterface;
use Webmozart\Puli\Locator\AbstractResourceLocator;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryLoaderInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\ResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpCacheLocator extends AbstractResourceLocator implements DirectoryLoaderInterface, AlternativePathLoaderInterface
{
    const FILE_PATHS_FILE = 'resources_file_paths.php';

    const DIR_PATHS_FILE = 'resources_dir_paths.php';

    const ALTERNATIVE_PATHS_FILE = 'resources_alt_paths.php';

    const TAGS_FILE = 'resources_tags.php';

    private $cacheDir;

    private $resources = array();

    private $filePaths;

    private $dirPaths;

    private $alternativePaths;

    private $tags;

    public function __construct($cacheDir, PatternFactoryInterface $patternFactory = null)
    {
        if (!file_exists($cacheDir.'/'.self::FILE_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::DIR_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::ALTERNATIVE_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::TAGS_FILE)) {
            throw new \InvalidArgumentException(sprintf(
                'The dump at "%s" is invalid.Please try to recreate it.',
                $cacheDir
            ));
        }

        parent::__construct($patternFactory);

        $this->cacheDir = $cacheDir;
    }

    public function getByTag($tag)
    {
        if (null === $this->tags) {
            $this->tags = require ($this->cacheDir.'/'.self::TAGS_FILE);
        }

        if (!isset($this->tags[$tag])) {
            return new ResourceCollection();
        }

        if (count($this->tags[$tag]) > 0 && is_string($this->tags[$tag][0])) {
            foreach ($this->tags[$tag] as $key => $path) {
                $this->tags[$tag][$key] = $this->get($path);
            }
        }

        return new LocalResourceCollection($this->tags[$tag]);
    }

    /**
     * @return string[]
     */
    public function getTags($path = null)
    {
        if (null === $this->tags) {
            $this->tags = require ($this->cacheDir.'/'.self::TAGS_FILE);
        }

        return array_keys($this->tags);
    }

    public function loadDirectoryEntries(DirectoryResourceInterface $directory)
    {
        return $this->findImpl(new GlobPattern(rtrim($directory->getPath(), '/').'/*'));
    }

    public function loadAlternativePaths(LocalResourceInterface $resource)
    {
        if (null === $this->alternativePaths) {
            $this->alternativePaths = require ($this->cacheDir.'/'.self::ALTERNATIVE_PATHS_FILE);
        }

        $path = $resource->getPath();

        if (isset($this->alternativePaths[$path])) {
            return $this->alternativePaths[$path];
        }

        return array();
    }

    protected function getImpl($path)
    {
        // Return the resource if it was already loaded
        if (isset($this->resources[$path])) {
            return $this->resources[$path];
        }

        // Load the mapping of repository paths to file paths if needed
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        // Create LazyFileResource instances for files
        if (array_key_exists($path, $this->filePaths)) {
            $this->initFile($path);

            return $this->resources[$path];
        }

        // Load the mapping of repository paths to directory paths if needed
        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        // Create LazyDirectoryResource instances for directories
        if (array_key_exists($path, $this->dirPaths)) {
            $this->initDirectory($path);

            return $this->resources[$path];
        }

        throw new ResourceNotFoundException(sprintf(
            'The resource "%s" was not found.',
            $path
        ));
    }

    protected function findImpl(PatternInterface $pattern)
    {
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        $resources = array();
        $staticPrefix = $pattern->getStaticPrefix();
        $regExp = $pattern->getRegularExpression();

        foreach ($this->resources as $path => $resource) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($path, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $path)) {
                continue;
            }

            $resources[$path] = $resource;
        }

        foreach ($this->filePaths as $path => $localPath) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($path, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $path)) {
                continue;
            }

            $this->initFile($path);

            $resources[$path] = $this->resources[$path];
        }

        foreach ($this->dirPaths as $path => $localPath) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($path, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $path)) {
                continue;
            }

            $this->initDirectory($path);

            $resources[$path] = $this->resources[$path];
        }

        ksort($resources);

        return new LocalResourceCollection(array_values($resources));
    }

    protected function containsImpl($path)
    {
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        return isset($this->resources[$path])
            // The path may be NULL, so use array_key_exists()
            || array_key_exists($path, $this->filePaths)
            || array_key_exists($path, $this->dirPaths);
    }

    protected function containsPatternImpl(PatternInterface $pattern)
    {
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
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

    private function initFile($path)
    {
        $this->resources[$path] = LocalFileResource::forPath($path, $this->filePaths[$path], $this);

        // Remove to reduce number of loops in future calls
        unset($this->filePaths[$path]);

        // Maintain order of resources
        ksort($this->resources);
    }

    private function initDirectory($path)
    {
        if (null !== $this->dirPaths[$path]) {
            $directory = LocalDirectoryResource::forPath($path, $this->dirPaths[$path], $this, $this);
        } else {
            $directory = DirectoryResource::forPath($path, $this);
        }

        $this->resources[$path] = $directory;

        // Remove to reduce number of loops in future calls
        unset($this->dirPaths[$path]);

        // Maintain order of resources
        ksort($this->resources);
    }
}
