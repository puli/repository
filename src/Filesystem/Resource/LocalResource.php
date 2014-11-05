<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Filesystem\Resource;

use Webmozart\Puli\Filesystem\FilesystemException;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\Resource\UnsupportedResourceException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class LocalResource implements LocalResourceInterface
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
     * @var string[]|null
     */
    private $alternativePaths;

    /**
     * @var AlternativePathLoaderInterface
     */
    private $alternativesLoader;

    /**
     * @param                                $path
     * @param                                $localPath
     * @param AlternativePathLoaderInterface $alternativesLoader
     *
     * @return static
     */
    public static function forPath($path, $localPath, AlternativePathLoaderInterface $alternativesLoader = null)
    {
        $resource = new static($localPath, $alternativesLoader);
        $resource->path = $path;

        return $resource;
    }

    public function __construct($localPath, AlternativePathLoaderInterface $alternativesLoader = null)
    {
        if (!file_exists($localPath)) {
            throw new FilesystemException(sprintf(
                'The file "%s" does not exist.',
                $localPath
            ));
        }

        $this->path = $localPath;
        $this->localPath = $localPath;
        $this->alternativesLoader = $alternativesLoader;
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
        return basename($this->path);
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     * @return string[]
     */
    public function getAlternativePaths()
    {
        if (null === $this->alternativePaths) {
            $this->alternativePaths = $this->alternativesLoader
                ? $this->alternativesLoader->loadAlternativePaths($this)
                : array();

            $this->alternativePaths[] = $this->localPath;

            // Remove the now unneeded reference
            $this->alternativesLoader = null;
        }

        return $this->alternativePaths;
    }

    public function copyTo($path)
    {
        $copy = clone $this;
        $copy->path = $path;

        return $copy;
    }

    public function override(ResourceInterface $resource)
    {
        if (!$resource instanceof LocalResourceInterface) {
            throw new UnsupportedResourceException('Can only override other local resources.');
        }

        $override = clone $this;
        $override->path = $resource->getPath();
        $override->alternativePaths = array_merge(
            $resource->getAlternativePaths(),
            $override->getAlternativePaths()
        );

        return $override;
    }
}
