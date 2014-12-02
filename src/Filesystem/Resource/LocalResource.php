<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Resource;

use Puli\Repository\Filesystem\FilesystemException;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Repository\UnsupportedResourceException;
use Puli\Repository\Resource\AttachableResourceInterface;
use Puli\Repository\Resource\ResourceInterface;

/**
 * Base class for local resources.
 *
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
     * Creates a directory that is already attached to a repository.
     *
     * @param ResourceRepositoryInterface $repo      The repository.
     * @param string                      $path      The path in the repository.
     * @param string                      $localPath The path on the local file
     *                                               system.
     *
     * @return static The created resource.
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

    /**
     * Creates a new local resource.
     *
     * @param string $localPath The path on the local file system.
     *
     * @throws FilesystemException If the path does not exist.
     */
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
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->path ? basename($this->path) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLocalPaths()
    {
        if (null === $this->overriddenPaths) {
            $this->loadOverriddenPaths();
        }

        $paths = $this->overriddenPaths;
        $paths[] = $this->localPath;

        return $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        $this->path = $path;

        if ($repo instanceof OverriddenPathLoaderInterface) {
            $this->pathLoader = $repo;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $this->path = null;
        $this->pathLoader = null;
    }

    /**
     * {@inheritdoc}
     */
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
