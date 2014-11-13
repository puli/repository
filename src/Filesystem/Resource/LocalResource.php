<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Filesystem\Resource;

use Puli\Filesystem\FilesystemException;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Repository\UnsupportedResourceException;
use Puli\Resource\AttachableResourceInterface;
use Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class LocalResource implements LocalResourceInterface, AttachableResourceInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $localPath;

    /**
     * @var OverriddenPathLoaderInterface|null
     */
    private $pathLoader;

    /**
     * @var string[]|null
     */
    private $overriddenPaths;

    /**
     * @param ResourceRepositoryInterface $repo
     * @param                             $path
     * @param                             $localPath
     *
     * @return static
     */
    public static function createAttached(ResourceRepositoryInterface $repo, $path, $localPath)
    {
        $resource = new static($localPath);
        $resource->path = $path;

        if ($repo instanceof OverriddenPathLoaderInterface) {
            $resource->pathLoader = $repo;
        }

        return $resource;
    }

    public function __construct($localPath)
    {
        if (!file_exists($localPath)) {
            throw new FilesystemException(sprintf(
                'The file "%s" does not exist.',
                $localPath
            ));
        }

        $this->localPath = $localPath;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->path ? basename($this->path) : null;
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    public function getAllLocalPaths()
    {
        if (null === $this->overriddenPaths) {
            $this->loadOverriddenPaths();
        }

        $paths = $this->overriddenPaths;
        $paths[] = $this->localPath;

        return $paths;
    }

    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        $this->path = $path;

        if ($repo instanceof OverriddenPathLoaderInterface) {
            $this->pathLoader = $repo;
        }
    }

    public function detach()
    {
        $this->path = null;
        $this->pathLoader = null;
    }

    public function override(ResourceInterface $resource)
    {
        if (!$resource instanceof LocalResourceInterface) {
            throw new UnsupportedResourceException('Can only override other local resources.');
        }

        if (null === $this->overriddenPaths) {
            $this->loadOverriddenPaths();
        }

        $this->overriddenPaths = array_merge(
            $resource->getAllLocalPaths(),
            $this->overriddenPaths
        );
    }

    private function loadOverriddenPaths()
    {
        $this->overriddenPaths = $this->pathLoader
            ? $this->pathLoader->loadOverriddenPaths($this)
            : array();
        $this->pathLoader = null;
    }
}
