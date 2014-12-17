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

use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\Filesystem\Resource\LocalResource;
use Puli\Repository\Filesystem\Resource\LocalResourceCollection;
use Puli\Repository\Filesystem\Resource\OverriddenPathLoader;
use Puli\Repository\InvalidPathException;
use Puli\Repository\NoDirectoryException;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\Resource;
use Puli\Repository\Resource\VirtualDirectoryResource;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\ResourceRepository;
use Puli\Repository\Selector\Selector;
use Puli\Repository\UnsupportedResourceException;
use Webmozart\PathUtil\Path;

/**
 * A repository that reads from a PHP file cache.
 *
 * The cache can be populated using another repository with the
 * {@link dumpRepository} method:
 *
 * ```php
 * use Puli\Repository\Filesystem\PhpCacheRepository;
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/css', '/path/to/css');
 *
 * PhpCacheRepository::dumpRepository($repo, '/path/to/cache');
 * ```
 *
 * This method generates a couple of ".php" files in the given cache directory.
 * Pass this directory to the {@link __construct}:
 *
 * ```php
 * use Puli\Repository\Filesystem\PhpCacheRepository;
 *
 * $repo = new PhpCacheRepository('/path/to/cache');
 *
 * echo $repo->get('/css/style.css')->getLocalPath();
 * // => /path/to/css/style.css
 * ```
 *
 * All resources contained in the repository passed to {@link dumpRepository}
 * can be accessed. Note that only resources implementing either
 * {@link LocalResource} or {@link DirectoryResource} are included in the dump.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpCacheRepository implements ResourceRepository, OverriddenPathLoader
{
    /**
     * The name of the file caching the paths of all files.
     */
    const FILE_PATHS_FILE = 'resources_file_paths.php';

    /**
     * The name of the file caching the paths of all directories.
     */
    const DIR_PATHS_FILE = 'resources_dir_paths.php';

    /**
     * The name of the file caching the overridden paths.
     */
    const OVERRIDDEN_PATHS_FILE = 'resources_overridden_paths.php';

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var LocalResource[]|VirtualDirectoryResource[]
     */
    private $resources = array();

    /**
     * @var string[]
     */
    private $filePaths;

    /**
     * @var string[]
     */
    private $dirPaths;

    /**
     * @var array[]
     */
    private $overriddenPaths;

    /**
     * Dumps a repository into the given target path.
     *
     * The target path can then be passed to {@link __construct}.
     *
     * This method creates a list of ".php" files that contain mappings of
     * repository paths to local file paths. Hence any resources that don't
     * implement {@link LocalResource} are ignored. Resources that implement
     * {@link DirectoryResource} are always included, but their local path may
     * be empty.
     *
     * @param ResourceRepository $repo       The dumped repository.
     * @param string             $targetPath The path to the directory where the
     *                                       dumped files should be stored.
     *
     * @throws NoDirectoryException If the target path is not a directory.
     */
    public static function dumpRepository(ResourceRepository $repo, $targetPath)
    {
        $filePaths = array();
        $dirPaths = array();
        $overriddenPaths = array();

        // Extract the paths and alternative paths of each resource
        self::extractPaths($repo->get('/'), $filePaths, $dirPaths, $overriddenPaths);

        // Create the directory if it doesn't exist
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        if (!is_dir($targetPath)) {
            throw new NoDirectoryException($targetPath);
        }

        file_put_contents($targetPath.'/'.self::FILE_PATHS_FILE, "<?php\n\nreturn ".var_export($filePaths, true).";");
        file_put_contents($targetPath.'/'.self::DIR_PATHS_FILE, "<?php\n\nreturn ".var_export($dirPaths, true).";");
        file_put_contents($targetPath.'/'.self::OVERRIDDEN_PATHS_FILE, "<?php\n\nreturn ".var_export($overriddenPaths, true).";");
    }

    /**
     * Extracts path information of a resource.
     *
     * @param Resource $resource        The resource.
     * @param array    $filePaths       Collects the paths of all files.
     * @param array    $dirPaths        Collects the paths of all directories.
     * @param array    $overriddenPaths Collects the overridden paths.
     */
    private static function extractPaths(Resource $resource, array &$filePaths, array &$dirPaths, array &$overriddenPaths)
    {
        $path = $resource->getPath();

        if (!($resource instanceof LocalResource || $resource instanceof DirectoryResource)) {
            throw new UnsupportedResourceException(sprintf(
                'PhpCacheDumper only works with implementations of '.
                'LocalResource or DirectoryResource. Got: %s',
                get_class($resource)
            ));
        }

        if ($resource instanceof LocalResource) {
            $allLocalPaths = $resource->getAllLocalPaths();
            $localPath = array_pop($allLocalPaths);

            if (count($allLocalPaths) > 0) {
                $overriddenPaths[$path] = $allLocalPaths;
            }
        } else {
            // For directories that don't implement LocalResource, store null as
            // local path
            $localPath = null;
        }

        if ($resource instanceof DirectoryResource) {
            $dirPaths[$path] = $localPath;
        } else {
            $filePaths[$path] = $localPath;
        }

        // Recursively enter the contents of directories
        if ($resource instanceof DirectoryResource) {
            foreach ($resource->listEntries() as $entry) {
                self::extractPaths($entry, $filePaths, $dirPaths, $overriddenPaths);
            }
        }
    }

    /**
     * Creates a new repository.
     *
     * You should pass the same directory that you previously passed to
     * {@link dumpRepository} as target path. If that directory does not exist
     * or if cache files are missing, an exception is thrown.
     *
     * @param string $cacheDir The path to the directory that contains the
     *                         dumped files.
     *
     * @throws \RuntimeException If the dump is invalid.
     */
    public function __construct($cacheDir)
    {
        if (!file_exists($cacheDir.'/'.self::FILE_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::DIR_PATHS_FILE) ||
            !file_exists($cacheDir.'/'.self::OVERRIDDEN_PATHS_FILE)) {
            throw new \RuntimeException(sprintf(
                'The dump at "%s" is invalid. Please recreate it.',
                $cacheDir
            ));
        }

        $this->cacheDir = $cacheDir;
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
            'The resource "%s" does not exist.',
            $path
        ));
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

            foreach ($this->resources as $path => $resource) {
                // strpos() is slightly faster than substr() here
                if (0 !== strpos($path, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $path)) {
                    continue;
                }

                return true;
            }

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

    /**
     * {@inheritdoc}
     */
    public function listDirectory($path)
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

        if (null === $this->filePaths) {
            $this->filePaths = require ($this->cacheDir.'/'.self::FILE_PATHS_FILE);
        }

        if (null === $this->dirPaths) {
            $this->dirPaths = require ($this->cacheDir.'/'.self::DIR_PATHS_FILE);
        }

        $path = Path::canonicalize($path);
        $isLoaded = isset($this->resources[$path]);
        $isUnloadedDir = array_key_exists($path, $this->dirPaths);
        $isUnloadedFile = array_key_exists($path, $this->filePaths);

        if (!$isLoaded && !$isUnloadedDir && !$isUnloadedFile) {
            throw new ResourceNotFoundException(sprintf(
                'The directory "%s" does not exist.',
                $path
            ));
        }

        if ($isUnloadedFile || $isLoaded && !($this->resources[$path] instanceof DirectoryResource)) {
            throw new NoDirectoryException(sprintf(
                'The resource "%s" is not a directory.',
                $path
            ));
        }

        $staticPrefix = rtrim($path, '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        $resources = array();

        foreach ($this->resources as $resourcePath => $resource) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($resourcePath, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $resourcePath)) {
                continue;
            }

            $resources[$resourcePath] = $resource;
        }

        foreach ($this->filePaths as $resourcePath => $localPath) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($resourcePath, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $resourcePath)) {
                continue;
            }

            $this->initFile($resourcePath);

            $resources[$resourcePath] = $this->resources[$resourcePath];
        }

        foreach ($this->dirPaths as $resourcePath => $localPath) {
            // strpos() is slightly faster than substr() here
            if (0 !== strpos($resourcePath, $staticPrefix)) {
                continue;
            }

            if (!preg_match($regExp, $resourcePath)) {
                continue;
            }

            $this->initDirectory($resourcePath);

            $resources[$resourcePath] = $this->resources[$resourcePath];
        }

        ksort($resources);

        return new LocalResourceCollection(array_values($resources));
    }

    /**
     * {@inheritdoc}
     */
    public function loadOverriddenPaths(LocalResource $resource)
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
        $this->resources[$path] = new LocalFileResource($this->filePaths[$path], $path, $this);
        $this->resources[$path]->attachTo($this);

        // Remove to reduce number of loops in future calls
        unset($this->filePaths[$path]);

        // Maintain order of resources
        ksort($this->resources);
    }

    private function initDirectory($path)
    {
        if (null !== $this->dirPaths[$path]) {
            $directory = new LocalDirectoryResource($this->dirPaths[$path], $path, $this);
        } else {
            $directory = new VirtualDirectoryResource($path);
        }

        $directory->attachTo($this);

        $this->resources[$path] = $directory;

        // Remove to reduce number of loops in future calls
        unset($this->dirPaths[$path]);

        // Maintain order of resources
        ksort($this->resources);
    }
}
