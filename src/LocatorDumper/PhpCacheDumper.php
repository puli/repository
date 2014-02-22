<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\LocatorDumper;

use Webmozart\Puli\Locator\PhpCacheLocator;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\ResourceInterface;

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

        file_put_contents($targetPath.'/'. PhpCacheLocator::FILE_PATHS_FILE, "<?php\n\nreturn ".var_export($filePaths, true).";");
        file_put_contents($targetPath.'/'. PhpCacheLocator::DIR_PATHS_FILE, "<?php\n\nreturn ".var_export($dirPaths, true).";");
        file_put_contents($targetPath.'/'. PhpCacheLocator::ALTERNATIVE_PATHS_FILE, "<?php\n\nreturn ".var_export($alternativePaths, true).";");
        file_put_contents($targetPath.'/'. PhpCacheLocator::TAGS_FILE, "<?php\n\nreturn ".var_export($tags, true).";");
    }

    private function extractPaths(ResourceInterface $resource, array &$filePaths, array &$dirPaths, array &$alternativePaths)
    {
        $repositoryPath = $resource->getPath();
        $altPaths = $resource->getAlternativePaths();

        if ($resource instanceof DirectoryResourceInterface) {
            $dirPaths[$repositoryPath] = $resource->getRealPath();
        } else {
            $filePaths[$repositoryPath] = $resource->getRealPath();
        }

        // Discard the current path, we already have that information
        if (count($altPaths) > 1) {
            array_pop($altPaths);

            $alternativePaths[$repositoryPath] = $altPaths;
        }

        // Recurse into the contents of directories
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($resource as $child) {
                $this->extractPaths($child, $filePaths, $dirPaths, $alternativePaths);
            }
        }
    }
}
