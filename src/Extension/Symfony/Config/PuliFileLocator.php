<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Symfony\Config;

use Puli\Filesystem\Resource\LocalResourceInterface;
use Puli\ResourceNotFoundException;
use Puli\ResourceRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliFileLocator implements ChainableFileLocatorInterface
{
    /**
     * @var ResourceRepositoryInterface
     */
    private $repo;

    public function __construct(ResourceRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function supports($path)
    {
        return isset($path[0]) && '/' === $path[0];
    }

    /**
     * Returns a full path for a given Puli path.
     *
     * @param mixed   $path The Puli path to locate
     * @param string  $currentPath    The current path
     * @param boolean $first          Whether to return the first occurrence or
     *                                an array of file names
     *
     * @return string|array The full path to the file|An array of file paths
     *
     * @throws \InvalidArgumentException When the path is not found
     */
    public function locate($path, $currentPath = null, $first = true)
    {
        // Accept actual file paths
        if (file_exists($path)) {
            return $path;
        }

        if (null !== $currentPath && file_exists($currentPath.'/'.$path)) {
            throw new \RuntimeException(sprintf(
                'You tried to load the file "%s" using a relative path. '.
                'This functionality is not supported due to a limitation in '.
                'Symfony, because then this file cannot be overridden anymore. '.
                'Please pass the absolute Puli path instead.',
                $path
            ));
        }

        try {
            $resource = $this->repo->get($path);

            if (!$resource instanceof LocalResourceInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'The file "%s" is not a local file.',
                    $path
                ));
            }

            return $first
                ? $resource->getLocalPath()
                : array_reverse($resource->getAllLocalPaths());
        } catch (ResourceNotFoundException $e) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" could not be found.',
                $path
            ), 0, $e);
        }
    }
}
