<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Filesystem\Dumper;

use Webmozart\Puli\Filesystem\PhpCacheLocator;
use Webmozart\Puli\Filesystem\Resource\LocalResourceInterface;
use Webmozart\Puli\Locator\Dumper\ResourceLocatorDumperInterface;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\Resource\UnsupportedResourceException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpCacheDumper implements ResourceLocatorDumperInterface
{
    public function dumpLocator(ResourceLocatorInterface $locator, $targetPath)
    {
        $filePaths = array();
        $dirPaths = array();
        $alternativePaths = array();
        $tags = array();

        // Extract the paths and alternative paths of each resource
        $this->extractPaths($locator->get('/'), $filePaths, $dirPaths, $alternativePaths);

        // Remember which resource has which tag
        foreach ($locator->getTags() as $tag) {
            $tags[$tag] = $locator->getByTag($tag)->getPaths();
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

        file_put_contents($targetPath.'/'.PhpCacheLocator::FILE_PATHS_FILE, "<?php\n\nreturn ".var_export($filePaths, true).";");
        file_put_contents($targetPath.'/'.PhpCacheLocator::DIR_PATHS_FILE, "<?php\n\nreturn ".var_export($dirPaths, true).";");
        file_put_contents($targetPath.'/'.PhpCacheLocator::ALTERNATIVE_PATHS_FILE, "<?php\n\nreturn ".var_export($alternativePaths, true).";");
        file_put_contents($targetPath.'/'.PhpCacheLocator::TAGS_FILE, "<?php\n\nreturn ".var_export($tags, true).";");
    }

    private function extractPaths(ResourceInterface $resource, array &$filePaths, array &$dirPaths, array &$alternativePaths)
    {
        $path = $resource->getPath();

        if (!($resource instanceof LocalResourceInterface || $resource instanceof DirectoryResourceInterface)) {
            throw new UnsupportedResourceException(sprintf(
                'PhpCacheDumper only works with implementations of '.
                'LocalResourceInterface or DirectoryResourceInterface.Got: %s',
                get_class($resource)
            ));
        }

        if ($resource instanceof LocalResourceInterface) {
            $localPath = $resource->getLocalPath();
            $altPaths = $resource->getAlternativePaths();
        } else {
            // For directories that don't implement LocalResourceInterface,
            // store null as local path
            $localPath = null;
            $altPaths = null;
        }

        if ($resource instanceof DirectoryResourceInterface) {
            $dirPaths[$path] = $localPath;
        } else {
            $filePaths[$path] = $localPath;
        }

        // Discard the current path, we already have that information
        if (count($altPaths) > 1) {
            array_pop($altPaths);

            $alternativePaths[$path] = $altPaths;
        }

        // Recursively enter the contents of directories
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($resource->listEntries() as $entry) {
                $this->extractPaths($entry, $filePaths, $dirPaths, $alternativePaths);
            }
        }
    }
}
