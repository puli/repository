<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Configuration;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RepositoryConfiguration
{
    private $files = array();

    private $directories = array();

    private $tags = array();

    private $knownPaths = array();

    private $rootDirectory;

    public function __construct($rootDirectory = null)
    {
        if (!is_dir($rootDirectory)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" does not exist.',
                $rootDirectory
            ));
        }

        $this->rootDirectory = $rootDirectory ? rtrim($rootDirectory, '/').'/' : '';
    }

    public function getRootDirectory()
    {
        return rtrim($this->rootDirectory, '/');
    }

    public function setPath($repositoryPath, $pattern)
    {
        $paths = glob($this->rootDirectory.$pattern);

        if (0 === count($paths)) {
            throw new UnmatchedPatternException(sprintf(
                'The pattern "%s" did not match any file.',
                $pattern
            ));
        }

        $rootLength = strlen($this->rootDirectory);

        // If exactly one directory is matched, let the repository path point
        // to that directory
        if (1 === count($paths) && is_dir($paths[0])) {
            $this->addDirectory(rtrim(substr($paths[0], $rootLength), '/'), $repositoryPath);

            return;
        }

        // If multiple paths are matched, create sub-paths for each entry
        foreach ($paths as $path) {
            $nestedRepositoryPath = $repositoryPath.'/'.basename($path);

            if (is_dir($path)) {
                $this->addDirectory(rtrim(substr($path, $rootLength), '/'), $nestedRepositoryPath);
            } else {
                $this->addFile(substr($path, $rootLength), $nestedRepositoryPath);
            }
        }
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function getDirectories()
    {
        return $this->directories;
    }

    public function addTag($tag, $pattern)
    {
        $paths = glob($this->rootDirectory.$pattern);

        if (0 === count($paths)) {
            throw new UnmatchedPatternException(sprintf(
                'The pattern "%s" did not match any file.',
                $pattern
            ));
        }

        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = array();
        }

        $rootLength = strlen($this->rootDirectory);

        foreach ($paths as $path) {
            $path = rtrim(substr($path, $rootLength), '/');

            // Check whether the path was exported directly
            $isExported = isset($this->knownPaths[$path]);

            // Else check whether one of its parent directories was exported
            if (!$isExported) {
                foreach ($this->directories as $dirPaths) {
                    foreach ($dirPaths as $dirPath) {
                        if (0 === strpos($path, $dirPath.'/')) {
                            $isExported = true;

                            break 2;
                        }
                    }
                }
            }

            // Else report an error
            if (!$isExported) {
                throw new PathNotExportedException(sprintf(
                    'The path "%s" was not exported.',
                    $path
                ));
            }

            $this->tags[$tag][] = $path;
        }
    }

    public function getTags()
    {
        return $this->tags;
    }

    private function addFile($path, $repositoryPath)
    {
        if (!isset($this->files[$repositoryPath])) {
            $this->files[$repositoryPath][] = array();
        }

        $this->files[$repositoryPath][] = $path;
        $this->knownPaths[$path] = true;
    }

    private function addDirectory($path, $repositoryPath)
    {
        if (!isset($this->directories[$repositoryPath])) {
            $this->directories[$repositoryPath] = array();
        }

        $this->directories[$repositoryPath][] = $path;
        $this->knownPaths[$path] = true;
    }
}
