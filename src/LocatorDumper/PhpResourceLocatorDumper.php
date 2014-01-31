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

use Webmozart\Puli\Repository\ResourceRepositoryInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpResourceResourceLocatorDumper implements ResourceLocatorDumperInterface
{
    const PATHS_FILE = '/resources_paths.php';

    const TAGS_FILE = '/resources_tags.php';

    const CONFIG_FILE = '/resources_config.php';

    public function dumpLocator(ResourceRepositoryInterface $repository, $targetPath)
    {
        $paths = array();
        $root = $repository->getRootDirectory();
        $rootLength = strlen($root);

        foreach ($repository->getDirectories() as $dirRepositoryPath => $dirPaths) {
            foreach ($dirPaths as $dirPath) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $dirPath,
                        \FilesystemIterator::CURRENT_AS_PATHNAME |
                        \FilesystemIterator::SKIP_DOTS |
                        \FilesystemIterator::UNIX_PATHS
                    ),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                $dirPathLength = strlen($dirPath);

                if (0 === strpos($dirPath, $root)) {
                    $dirPath = substr($dirPath, $rootLength);
                }

                if (!isset($paths[$dirRepositoryPath])) {
                    $paths[$dirRepositoryPath] = array();
                }

                $paths[$dirRepositoryPath][] = $dirPath;

                foreach ($iterator as $path) {
                    $repositoryPath = $dirRepositoryPath.substr($path, $dirPathLength);

                    if (!isset($paths[$repositoryPath])) {
                        $paths[$repositoryPath] = array();
                    }

                    if (0 === strpos($path, $root)) {
                        $path = substr($path, $rootLength);
                    }

                    $paths[$repositoryPath][] = $path;
                }
            }
        }

        foreach ($repository->getFiles() as $dirRepositoryPath => $filePaths) {
            foreach ($filePaths as $filePath) {
                if (!isset($paths[$dirRepositoryPath])) {
                    $paths[$dirRepositoryPath] = array();
                }

                if (0 === strpos($filePath, $root)) {
                    $filePath = substr($filePath, $rootLength);
                }

                $paths[$dirRepositoryPath][] = $filePath;
            }
        }

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        if (!is_dir($targetPath)) {
            throw new \InvalidArgumentException(sprintf(
                'The path "%s" is not a directory.',
                $targetPath
            ));
        }

        $dumpedConfig = array(
            'root' => $repository->getRootDirectory(),
        );

        file_put_contents($targetPath.self::PATHS_FILE, "<?php\n\nreturn ".var_export($paths, true).";");
        file_put_contents($targetPath.self::TAGS_FILE, "<?php\n\nreturn ".var_export($repository->getTags(), true).";");
        file_put_contents($targetPath.self::CONFIG_FILE, "<?php\n\nreturn ".var_export($dumpedConfig, true).";");
    }
}
