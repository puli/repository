<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Resource;

use Puli\Repository\Filesystem\FilesystemException;
use Puli\Repository\Resource\AbstractResource;
use Puli\Repository\Resource\Resource;
use Puli\Repository\UnsupportedResourceException;

/**
 * Base class for local resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractLocalResource extends AbstractResource implements LocalResource
{
    /**
     * @var string
     */
    private $localPath;

    /**
     * @var string[]|null
     */
    private $overriddenPaths;

    /**
     * @var OverriddenPathLoader|null
     */
    private $pathLoader;

    /**
     * Creates a new local resource.
     *
     * @param string               $localPath  The path on the local file system.
     * @param string|null          $path       The repository path of the resource.
     * @param OverriddenPathLoader $pathLoader The loader for the overridden paths.
     */
    public function __construct($localPath, $path = null, OverriddenPathLoader $pathLoader = null)
    {
        parent::__construct($path);

        if (!file_exists($localPath)) {
            throw new FilesystemException(sprintf(
                'The file "%s" does not exist.',
                $localPath
            ));
        }

        $this->localPath = $localPath;
        $this->pathLoader = $pathLoader;
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
    public function override(Resource $resource)
    {
        if (!$resource instanceof LocalResource) {
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

    protected function preSerialize(array &$data)
    {
        parent::preSerialize($data);

        if (null === $this->overriddenPaths) {
            $this->loadOverriddenPaths();
        }

        $data[] = $this->localPath;
        $data[] = $this->overriddenPaths;
    }

    protected function postUnserialize(array $data)
    {
        $this->overriddenPaths = array_pop($data);
        $this->localPath = array_pop($data);

        parent::postUnserialize($data);
    }

    private function loadOverriddenPaths()
    {
        $this->overriddenPaths = $this->pathLoader
            ? $this->pathLoader->loadOverriddenPaths($this)
            : array();
        $this->pathLoader = null;
    }
}
