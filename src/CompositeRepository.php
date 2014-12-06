<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Resource\Collection\ResourceCollectionInterface;
use Puli\Repository\Resource\ResourceInterface;
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
 * use Puli\Repository\ResourceRepository;
 *
 * $puliRepo = new ResourceRepository();
 * $psr4Repo = new ResourceRepository();
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
 * use Puli\Repository\ResourceRepository;
 *
 * $repo = new CompositeRepository();
 * $repo->mount('/puli', function () {
 *     $repo = new ResourceRepository();
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
class CompositeRepository implements ResourceRepositoryInterface
{
    /**
     * @var ResourceRepositoryInterface[]|callable[]
     */
    private $repos = array();

    /**
     * Mounts a repository to a path.
     *
     * The repository may either be passed as {@link ResourceRepositoryInterface}
     * or as callable. If a callable is passed, the callable is invoked as soon
     * as the scheme is used for the first time. The callable should return a
     * {@link ResourceRepositoryInterface} object.
     *
     * @param string                               $path              An absolute path.
     * @param callable|ResourceRepositoryInterface $repositoryFactory The repository to use.
     *
     * @throws InvalidPathException If the path is invalid. The path must be a
     *                              non-empty string starting with "/".
     * @throws \InvalidArgumentException If the repository factory is invalid.
     */
    public function mount($path, $repositoryFactory)
    {
        if (!$repositoryFactory instanceof ResourceRepositoryInterface
                && !is_callable($repositoryFactory)) {
            throw new \InvalidArgumentException(
                'The repository factory should be a callable or an instance '.
                'of "Puli\Repository\ResourceRepositoryInterface".'
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

        if ('/' === $path) {
            throw new InvalidPathException('The root "/" cannot be mounted.');
        }

        $this->repos[Path::canonicalize($path)] = $repositoryFactory;
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

        unset($this->repos[Path::canonicalize($path)]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        list ($mountPoint, $subPath) = $this->parsePath($path);

        if (null === $mountPoint) {
            throw new ResourceNotFoundException(sprintf(
                'Could not find a matching mount point for the path "%s".',
                $path
            ));
        }

        return $this->getRepository($mountPoint)->get($subPath);
    }

    /**
     * {@inheritdoc}
     */
    public function find($selector)
    {
        list ($mountPoint, $subSelector) = $this->parsePath($selector);

        if (null === $mountPoint) {
            return new ResourceCollection();
        }

        return $this->getRepository($mountPoint)->find($subSelector);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        list ($mountPoint, $subSelector) = $this->parsePath($selector);

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
        list ($mountPoint, $subPath) = $this->parsePath($path);

        if (null === $mountPoint) {
            throw new ResourceNotFoundException(sprintf(
                'Could not find a matching mount point for the path "%s".',
                $path
            ));
        }

        return $this->getRepository($mountPoint)->listDirectory($subPath);
    }

    /**
     * {@inheritdoc}
     */
    public function findByTag($tag)
    {
        if ('' === $tag) {
            throw new \InvalidArgumentException('The tag must not be empty.');
        }

        if (!is_string($tag)) {
            throw new \InvalidArgumentException(sprintf(
                'The tag must be a string. Is: %s.',
                is_object($tag) ? get_class($tag) : gettype($tag)
            ));
        }

        $collection = new ResourceCollection();

        foreach ($this->repos as $repo) {
            try {
                $collection->merge($repo->findByTag($tag));
            } catch (ResourceNotFoundException $e) {
                // continue
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        $tags = array();

        foreach ($this->repos as $repo) {
            try {
                $tags = array_merge($tags, $repo->getTags());
            } catch (ResourceNotFoundException $e) {
                // continue
            }
        }

        // reindex
        return array_values(array_unique($tags));
    }

    private function parsePath($path)
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
     * @return ResourceRepositoryInterface The resource repository.
     *
     * @throws RepositoryFactoryException If the callable did not return an
     *                                    instance of {@link ResourceRepositoryInterface}.
     */
    private function getRepository($mountPoint)
    {
        if (is_callable($this->repos[$mountPoint])) {
            $callable = $this->repos[$mountPoint];
            $result = $callable($mountPoint);

            if (!$result instanceof ResourceRepositoryInterface) {
                throw new RepositoryFactoryException(sprintf(
                    'The value of type "%s" returned by the locator factory '.
                    'registered for the mount point "%s" does not implement '.
                    '"\Puli\Repository\ResourceRepositoryInterface".',
                    gettype($result),
                    $mountPoint
                ));
            }

            $this->repos[$mountPoint] = $result;
        }

        return $this->repos[$mountPoint];
    }
}
