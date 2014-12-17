<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use InvalidArgumentException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Uri\RepositoryFactoryException;
use Webmozart\PathUtil\Path;

/**
 * A repository combining multiple other repository instances.
 *
 * You can mount repositories to specific paths in the composite repository.
 * Requests for these paths will then be routed to the mounted repository:
 *
 * ```php
 * use Puli\Repository\CompositeRepository;
 * use Puli\Repository\InMemoryRepository;
 *
 * $puliRepo = new InMemoryRepository();
 * $psr4Repo = new InMemoryRepository();
 *
 * $repo = new CompositeRepository();
 * $repo->mount('/puli', $puliRepo);
 * $repo->mount('/psr4', $psr4Repo);
 *
 * $resource = $repo->get('/puli/css/style.css');
 * // => $puliRepo->get('/css/style.css');
 *
 * $resource = $repo->get('/psr4/Webmozart/Puli/Puli.php');
 * // => $psr4Repo->get('/Webmozart/Puli/Puli.php');
 * ```
 *
 * If not all repositories are needed in every request, you can pass callables
 * which create the repository on demand:
 *
 * ```php
 * use Puli\Repository\CompositeRepository;
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new CompositeRepository();
 * $repo->mount('/puli', function () {
 *     $repo = new InMemoryRepository();
 *     // configuration...
 *
 *     return $repo;
 * });
 * ```
 *
 * If a path is accessed that is not mounted, the repository acts as if the
 * path did not exist.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CompositeRepository implements ResourceRepository
{
    /**
     * @var ResourceRepository[]|callable[]
     */
    private $repos = array();

    /**
     * Mounts a repository to a path.
     *
     * The repository may either be passed as {@link ResourceRepository} or as
     * callable. If a callable is passed, the callable is invoked as soon as the
     * scheme is used for the first time. The callable should return a
     * {@link ResourceRepository} object.
     *
     * @param string                      $path              An absolute path.
     * @param callable|ResourceRepository $repositoryFactory The repository to use.
     *
     * @throws InvalidPathException If the path is invalid. The path must be a
     *                              non-empty string starting with "/".
     * @throws InvalidArgumentException If the repository factory is invalid.
     */
    public function mount($path, $repositoryFactory)
    {
        if (!$repositoryFactory instanceof ResourceRepository
                && !is_callable($repositoryFactory)) {
            throw new InvalidArgumentException(
                'The repository factory should be a callable or an instance '.
                'of "Puli\Repository\ResourceRepository".'
            );
        }

        if ('' === $path) {
            throw new InvalidPathException('The mount point must not be empty.');
        }

        if (!is_string($path)) {
            throw new InvalidPathException(sprintf(
                'The mount point must be a string. Is: %s.',
                is_object($path) ? get_class($path) : gettype($path)
            ));
        }

        if ('/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The mount point "%s" is not absolute.',
                $path
            ));
        }

        $path = Path::canonicalize($path);

        $this->repos[$path] = $repositoryFactory;

        // Prefer more specific mount points (e.g. "/app) over less specific
        // ones (e.g. "/")
        krsort($this->repos);
    }

    /**
     * Unmounts the repository mounted at a path.
     *
     * If no repository is mounted to this path, this method does nothing.
     *
     * @param string $path The path of the mount point.
     *
     * @throws InvalidPathException If the path is invalid. The path must be a
     *                              non-empty string starting with "/".
     */
    public function unmount($path)
    {
        if ('' === $path) {
            throw new InvalidPathException('The mount point must not be empty.');
        }

        if (!is_string($path)) {
            throw new InvalidPathException(sprintf(
                'The mount point must be a string. Is: %s.',
                is_object($path) ? get_class($path) : gettype($path)
            ));
        }

        if ('/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The mount point "%s" is not absolute.',
                $path
            ));
        }

        $path = Path::canonicalize($path);

        unset($this->repos[$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        list ($mountPoint, $subPath) = $this->splitPath($path);

        if (null === $mountPoint) {
            throw new ResourceNotFoundException(sprintf(
                'Could not find a matching mount point for the path "%s".',
                $path
            ));
        }

        $resource = $this->getRepository($mountPoint)->get($subPath);

        return '/' === $mountPoint ? $resource : $resource->createReference($path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($selector)
    {
        list ($mountPoint, $subSelector) = $this->splitPath($selector);

        if (null === $mountPoint) {
            return new ArrayResourceCollection();
        }

        $resources = $this->getRepository($mountPoint)->find($subSelector);
        $this->replaceByReferences($resources, $mountPoint);

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        list ($mountPoint, $subSelector) = $this->splitPath($selector);

        if (null === $mountPoint) {
            return false;
        }

        return $this->getRepository($mountPoint)->contains($subSelector);
    }

    /**
     * {@inheritdoc}
     */
    public function listDirectory($path)
    {
        list ($mountPoint, $subPath) = $this->splitPath($path);

        if (null === $mountPoint) {
            throw new ResourceNotFoundException(sprintf(
                'Could not find a matching mount point for the path "%s".',
                $path
            ));
        }

        $resources = $this->getRepository($mountPoint)->listDirectory($subPath);
        $this->replaceByReferences($resources, $mountPoint);

        return $resources;
    }

    /**
     * Splits a path into mount point and path.
     *
     * @param string $path The path to split.
     *
     * @return array An array with the mount point and the path. If no mount
     *               point was found, both are `null`.
     */
    private function splitPath($path)
    {
        if ('' === $path) {
            throw new InvalidPathException('The mount point must not be empty.');
        }

        if (!is_string($path)) {
            throw new InvalidPathException(sprintf(
                'The mount point must be a string. Is: %s.',
                is_object($path) ? get_class($path) : gettype($path)
            ));
        }

        if ('/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The mount point "%s" is not absolute.',
                $path
            ));
        }

        $path = Path::canonicalize($path);

        foreach ($this->repos as $mountPoint => $_) {
            if (Path::isBasePath($mountPoint, $path)) {
                // Special case "/": return the complete path
                if ('/' === $mountPoint) {
                    return array($mountPoint, $path);
                }

                return array($mountPoint, substr($path, strlen($mountPoint)));
            }
        }

        return array(null, null);
    }

    /**
     * If necessary constructs and returns the repository for the given mount
     * point.
     *
     * @param string $mountPoint An existing mount point.
     *
     * @return ResourceRepository The resource repository.
     *
     * @throws RepositoryFactoryException If the callable did not return an
     *                                    instance of {@link ResourceRepository}.
     */
    private function getRepository($mountPoint)
    {
        if (is_callable($this->repos[$mountPoint])) {
            $callable = $this->repos[$mountPoint];
            $result = $callable($mountPoint);

            if (!$result instanceof ResourceRepository) {
                throw new RepositoryFactoryException(sprintf(
                    'The value of type "%s" returned by the locator factory '.
                    'registered for the mount point "%s" does not implement '.
                    'ResourceRepository.',
                    gettype($result),
                    $mountPoint
                ));
            }

            $this->repos[$mountPoint] = $result;
        }

        return $this->repos[$mountPoint];
    }

    /**
     * Replaces all resources in the collection by references.
     *
     * If a resource "/resource" was loaded from a mount point "/mount", the
     * resource is replaced by a reference with the path "/mount/resource".
     *
     * @param ResourceCollection $resources  The resources to replace.
     * @param string             $mountPoint The mount point from which the
     *                                       resources were loaded.
     */
    private function replaceByReferences(ResourceCollection $resources, $mountPoint)
    {
        if ('/' !== $mountPoint) {
            foreach ($resources as $key => $resource) {
                $resources[$key] = $resource->createReference($mountPoint.$resource->getPath());
            }
        }
    }
}
