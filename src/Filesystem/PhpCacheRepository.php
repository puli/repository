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

use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Filesystem\Resource\LocalFileResource;
use Webmozart\Puli\Filesystem\Resource\LocalResource;
use Webmozart\Puli\Filesystem\Resource\LocalResourceCollection;
use Webmozart\Puli\Filesystem\Resource\LocalResourceInterface;
use Webmozart\Puli\Filesystem\Resource\OverriddenPathLoaderInterface;
use Webmozart\Puli\InvalidPathException;
use Webmozart\Puli\Util\Path;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\ResourceNotFoundException;
use Webmozart\Puli\ResourceRepositoryInterface;
use Webmozart\Puli\Util\Selector;
use Webmozart\Puli\UnsupportedResourceException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpCacheRepository implements ResourceRepositoryInterface, OverriddenPathLoaderInterface
{
    const FILE_PATHS_FILE = 'resources_file_paths.php';

    const DIR_PATHS_FILE = 'resources_dir_paths.php';

    const OVERRIDDEN_PATHS_FILE = 'resources_overridden_paths.php';

    const TAGS_FILE = 'resources_tags.php';

    private $cacheDir;

    /**
     * @var LocalResource[]|DirectoryResource[]
     */
    private $resources = array();

    private $filePaths;

    private $dirPaths;

    private $overriddenPaths;

    private $tags;

    public static function dumpRepository(ResourceRepositoryInterface $repo, $targetPath)
    {
        $filePaths = array();
        $dirPaths = array();
        $overriddenPaths = array();
        $tags = array();

        // Extract the paths and alternative paths of each resource
        self::extractPaths($repo->get('/'), $filePaths, $dirPaths, $overriddenPaths);

        // Remember which resource has which tag
        foreach ($repo->getTags() as $tag) {
            $tags[$tag] = $repo->getByTag($tag)->getPaths();
        }

        // Create the directory if it doesn't exist
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        if (!is_dir($targetPath)) {
            throw new \InvalidArgumentException(sprintf(
                'The path "%s" is not a directory.',
                $targetPath
            ));
        }

        file_put_contents($targetPath.'/'.self::FILE_PATHS_FILE, "<?php\n\nreturn ".var_export($filePaths, true).";");
        file_put_contents($targetPath.'/'.self::DIR_PATHS_FILE, "<?php\n\nreturn ".var_export($dirPaths, true).";");
        file_put_contents($targetPath.'/'.self::OVERRIDDEN_PATHS_FILE, "<?php\n\nreturn ".var_export($overriddenPaths, true).";");
        file_put_contents($targetPath.'/'.self::TAGS_FILE, "<?php\n\nreturn ".var_export($tags, true).";");
    }

    private static function extractPaths(ResourceInterface $resource, array &$filePaths, array &$dirPaths, array &$overriddenPaths)
    {
        $path = $resource->getPath();

        if (!($resource instanceof LocalResourceInterface || $resource instanceof DirectoryResourceInterface)) {
            throw new UnsupportedResourceException(sprintf(
                'PhpCacheDumper only works with implementations of '.
                'LocalResourceInterface or DirectoryResourceInterface. Got: %s',
                get_class($resource)
            ));
        }

        if ($resource instanceof LocalResourceInterface) {
            $allLocalPaths = $resource->getAllLocalPaths();
            $localPath = array_pop($allLocalPaths);

            if (count($allLocalPaths) > 0) {
                $overriddenPaths[$path] = $allLocalPaths;
            }
        } else {
            // For directories that don't implement LocalResourceInterface,
            // store null as local path
            $localPath = null;
        }

        if ($resource instanceof DirectoryResourceInterface) {
            $dirPaths[$path] = $localPath;
        } else {
            $filePaths[$path] = $localPath;
        }

        // Recursively enter the contents of directories
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($resource->listEntries() as $entry) {
                self::extractPaths($entry, $filePaths, $dirPaths, $overriddenPaths);
            }
        }
    }

    public function __construct($cacheDir)
    {
        if (!file_exists($cacheDir.'/'.self::FILE_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::DIR_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::OVERRIDDEN_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::TAGS_FILE)) {
            throw new \RuntimeException(sprintf(
                'The dump at "%s" is invalid. Please recreate it.',
                $cacheDir
            ));
        }

        $this->cacheDir = $cacheDir;
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

        // Return the resource if it was already loaded
        if (isset($this->resources[$path])) {
            return $this->resources[$path];
        }

        // Load the mapping of repository paths to file paths if needed
        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        // Create LocalFileResource instances for files
        if (array_key_exists($path, $this->filePaths)) {
            $this->initFile($path);

            return $this->resources[$path];
        }

        // Load the mapping of repository paths to directory paths if needed
        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        // Create LocalDirectoryResource instances for directories
        if (array_key_exists($path, $this->dirPaths)) {
            $this->initDirectory($path);

            return $this->resources[$path];
        }

        throw new ResourceNotFoundException(sprintf(
            'The resource "%s" was not found.',
            $path
        ));
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

        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        $staticPrefix = Selector::getStaticPrefix($selector);
        $resources = array();

        if (strlen($selector) > strlen($staticPrefix)) {
            $regExp = Selector::toRegEx($selector);

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

        if (isset($this->resources[$selector])) {
            $resources[] = $this->resources[$selector];
        }

        if (isset($this->filePaths[$selector])) {
            $this->initFile($selector);

            $resources[] = $this->resources[$selector];
        }

        if (isset($this->dirPaths[$selector])) {
            $this->initDirectory($selector);

            $resources[] = $this->resources[$selector];
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

        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        $selector = Path::canonicalize($selector);
        $staticPrefix = Selector::getStaticPrefix($selector);

        if (strlen($selector) > strlen($staticPrefix)) {
            $regExp = Selector::toRegEx($selector);

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

        return isset($this->resources[$selector])
            // The path may be NULL, so use array_key_exists()
            || array_key_exists($selector, $this->filePaths)
            || array_key_exists($selector, $this->dirPaths);
    }

    public function getByTag($tag)
    {
        if (null === $this->tags) {
            $this->tags = require ($this->cacheDir.'/'.self::TAGS_FILE);
        }

        if (!isset($this->tags[$tag])) {
            return new LocalResourceCollection();
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

    public function loadOverriddenPaths(LocalResourceInterface $resource)
    {
        if (null === $this->overriddenPaths) {
            $this->overriddenPaths = require ($this->cacheDir.'/'.self::OVERRIDDEN_PATHS_FILE);
        }

        $path = $resource->getPath();

        if (isset($this->overriddenPaths[$path])) {
            return $this->overriddenPaths[$path];
        }

        return array();
    }

    private function initFile($path)
    {
        $this->resources[$path] = LocalFileResource::createAttached($this, $path, $this->filePaths[$path]);

        // Remove to reduce number of loops in future calls
        unset($this->filePaths[$path]);

        // Maintain order of resources
        ksort($this->resources);
    }

    private function initDirectory($path)
    {
        if (null !== $this->dirPaths[$path]) {
            $directory = LocalDirectoryResource::createAttached($this, $path, $this->dirPaths[$path]);
        } else {
            $directory = DirectoryResource::createAttached($this, $path);
        }

        $this->resources[$path] = $directory;

        // Remove to reduce number of loops in future calls
        unset($this->dirPaths[$path]);

        // Maintain order of resources
        ksort($this->resources);
    }
}
