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

use Webmozart\Puli\LocatorDumper\PhpResourceResourceLocatorDumper;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpResourceLocator implements ResourceLocatorInterface
{
    private $dumpLocation;

    private $config;

    private $paths;

    private $tags;

    public function __construct($dumpLocation)
    {
        if (!file_exists($dumpLocation.PhpResourceResourceLocatorDumper::PATHS_FILE) ||
            !file_exists($dumpLocation.'/resources_tags.php') ||
            !file_exists($dumpLocation.PhpResourceResourceLocatorDumper::CONFIG_FILE)) {
            throw new \InvalidArgumentException(sprintf(
                'The dump at "%s" is invalid. Please try to recreate it.',
                $dumpLocation
            ));
        }

        $this->dumpLocation = $dumpLocation;
    }

    public function get($repositoryRepositoryPath)
    {
        if (null === $this->config) {
            $this->config = require ($this->dumpLocation.PhpResourceResourceLocatorDumper::CONFIG_FILE);
        }

        if (null === $this->paths) {
            $this->paths = require ($this->dumpLocation.PhpResourceResourceLocatorDumper::PATHS_FILE);
        }

        if (!isset($this->paths[$repositoryRepositoryPath])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" was not found.',
                $repositoryRepositoryPath
            ));
        }

        if ($this->paths[$repositoryRepositoryPath] instanceof ResourceInterface) {
            return $this->paths[$repositoryRepositoryPath];
        }

        return $this->resolveResource($repositoryRepositoryPath);
    }

    public function getResources($pattern)
    {
        $firstWildcard = strpos($pattern, '*');

        // If the pattern contains no asterisk, it must refer to a specific
        // resource
        if (0 === $firstWildcard) {
            return array($this->get($pattern));
        }

        if (null === $this->config) {
            $this->config = require ($this->dumpLocation.PhpResourceResourceLocatorDumper::CONFIG_FILE);
        }

        if (null === $this->paths) {
            $this->paths = require ($this->dumpLocation.PhpResourceResourceLocatorDumper::PATHS_FILE);
        }


    }

    public function getByTag($tag)
    {

    }

    public function listDirectory($repositoryPath)
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
