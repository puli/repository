<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Repository;

use Webmozart\Puli\Dumper\PhpRepositoryDumper;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpDumpRepository implements ResourceRepositoryInterface
{
    private $dumpLocation;

    private $config;

    private $paths;

    private $tags;

    public function __construct($dumpLocation)
    {
        if (!file_exists($dumpLocation.PhpRepositoryDumper::PATHS_FILE) ||
            !file_exists($dumpLocation.'/resources_tags.php') ||
            !file_exists($dumpLocation.PhpRepositoryDumper::CONFIG_FILE)) {
            throw new \InvalidArgumentException(sprintf(
                'The dump at "%s" is invalid. Please try to recreate it.',
                $dumpLocation
            ));
        }

        $this->dumpLocation = $dumpLocation;
    }

    public function getResource($repositoryPath)
    {
        if (null === $this->config) {
            $this->config = require ($this->dumpLocation.PhpRepositoryDumper::CONFIG_FILE);
        }

        if (null === $this->paths) {
            $this->paths = require ($this->dumpLocation.PhpRepositoryDumper::PATHS_FILE);
        }

        if (!isset($this->paths[$repositoryPath])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" was not found.',
                $repositoryPath
            ));
        }

        if ($this->paths[$repositoryPath] instanceof ResourceInterface) {
            return $this->paths[$repositoryPath];
        }

        return $this->resolveResource($repositoryPath);
    }

    public function getResources($pattern)
    {
        $firstWildcard = strpos($pattern, '*');

        // If the pattern contains no asterisk, it must refer to a specific
        // resource
        if (0 === $firstWildcard) {
            return array($this->getResource($pattern));
        }

        if (null === $this->config) {
            $this->config = require ($this->dumpLocation.PhpRepositoryDumper::CONFIG_FILE);
        }

        if (null === $this->paths) {
            $this->paths = require ($this->dumpLocation.PhpRepositoryDumper::PATHS_FILE);
        }


    }

    public function getTaggedResources($tag)
    {

    }

    private function resolveResource($repositoryPath)
    {
        foreach ($this->paths[$repositoryPath] as $key => $path) {
            $this->paths[$repositoryPath][$key] = $this->config['root'].$path;
        }

        if (is_dir($this->paths[$repositoryPath][0])) {
            $this->paths[$repositoryPath] = new DirectoryResource(
                $repositoryPath,
                $this->paths[$repositoryPath]
            );
        } else {
            $this->paths[$repositoryPath] = new FileResource(
                $repositoryPath,
                array_pop($this->paths[$repositoryPath]),
                $this->paths[$repositoryPath]
            );
        }

        return $this->paths[$repositoryPath];
    }
}
