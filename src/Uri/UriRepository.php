<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Uri;

use InvalidArgumentException;
use Puli\Repository\InvalidPathException;
use Puli\Repository\NoDirectoryException;
use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\ResourceRepository;

/**
 * A repository which delegates to other repositories based on URI schemes.
 *
 * Repositories can be registered for specific URI schemes. Resource requests
 * for URIs with that scheme will then be routed to the appropriate
 * repository:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 * use Puli\Repository\Uri\UriRepository;
 *
 * $puliRepo = new InMemoryRepository();
 * $psr4Repo = new InMemoryRepository();
 *
 * $repo = new UriRepository();
 * $repo->register('puli', $puliRepo);
 * $repo->register('psr4', $psr4Repo);
 *
 * $resource = $repo->get('puli:///css/style.css');
 * // => $puliRepo->get('/css/style.css');
 *
 * $resource = $repo->get('psr4:///Webmozart/Puli/Puli.php');
 * // => $psr4Repo->get('/Webmozart/Puli/Puli.php');
 * ```
 *
 * If not all repositories are needed in every request, you can pass callables
 * which create the repository on demand:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 * use Puli\Repository\Uri\UriRepository;
 *
 * $repo = new UriRepository();
 * $repo->register('puli', function () {
 *     $repo = new InMemoryRepository();
 *     // configuration...
 *
 *     return $repo;
 * });
 * ```
 *
 * The first registered scheme is also registered as default scheme. When
 * reading paths from that scheme, the protocol may be omitted:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 * use Puli\Repository\Uri\UriRepository;
 *
 * $puliRepo = new InMemoryRepository();
 * $psr4Repo = new InMemoryRepository();
 *
 * $repo = new UriRepository();
 * $repo->register('puli', $puliRepo);
 *
 * $resource = $repo->get('/css/style.css');
 * // => $puliRepo->get('/css/style.css');
 * ```
 *
 * The default scheme can be changed with {@link setDefaultScheme}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriRepository implements UriRepositoryInterface
{
    /**
     * @var callable[]|ResourceRepository[]
     */
    private $repos = array();

    /**
     * @var string|null
     */
    private $defaultScheme;

    /**
     * Registers a repository for a given scheme.
     *
     * The repository may either be passed as {@link ResourceRepository}
     * or as callable. If a callable is passed, the callable is invoked as soon
     * as the scheme is used for the first time. The callable should return a
     * {@link ResourceRepository} object.
     *
     * @param string                               $scheme            A URI scheme.
     * @param callable|ResourceRepository $repositoryFactory The repository to use.
     *
     * @throws InvalidArgumentException If the repository factory or the URI
     *                                  scheme is invalid.
     */
    public function register($scheme, $repositoryFactory)
    {
        if (!$repositoryFactory instanceof ResourceRepository
                && !is_callable($repositoryFactory)) {
            throw new InvalidArgumentException(
                'The repository factory should be a callable or an instance '.
                'of "Puli\Repository\ResourceRepository".'
            );
        }

        if (!is_string($scheme)) {
            throw new InvalidArgumentException(sprintf(
                'The scheme must be a string, but is a "%s".',
                gettype($scheme)
            ));
        }

        if (!ctype_alnum($scheme)) {
            throw new InvalidArgumentException(sprintf(
                'The scheme "%s" should consist of letters and digits only.',
                $scheme
            ));
        }

        if (!ctype_alpha($scheme[0])) {
            throw new InvalidArgumentException(sprintf(
                'The first character of the scheme "%s" should be a letter.',
                $scheme
            ));
        }

        $this->repos[$scheme] = $repositoryFactory;

        if (null === $this->defaultScheme) {
            $this->defaultScheme = $scheme;
        }
    }

    /**
     * Unregisters the given scheme.
     *
     * Unknown schemes are ignored.
     *
     * @param string $scheme A URI scheme.
     */
    public function unregister($scheme)
    {
        unset($this->repos[$scheme]);

        if ($scheme === $this->defaultScheme) {
            $this->defaultScheme = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedSchemes()
    {
        return array_keys($this->repos);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultScheme()
    {
        return $this->defaultScheme;
    }

    /**
     * Sets the scheme to use when paths are passed instead of URIs.
     *
     * When a default scheme is registered, paths are automatically translated
     * to URIs:
     *
     * ```php
     * $repo->setDefaultScheme('puli');
     *
     * $repo->get('/css/style.css')
     * // => $repo->get('puli:///css/style.css');
     * ```
     *
     * Unless this method is called, the first registered scheme is the default
     * scheme.
     *
     * @param string $scheme A URI scheme.
     *
     * @throws UnsupportedSchemeException If the URI scheme is not supported.
     */
    public function setDefaultScheme($scheme)
    {
        if (!isset($this->repos[$scheme])) {
            throw new UnsupportedSchemeException(sprintf(
                'The scheme "%s" was not registered.',
                $scheme
            ));
        }

        $this->defaultScheme = $scheme;
    }

    /**
     * Returns the resource at the given URI.
     *
     * @param string $uri The URI to the resource. If a path is passed, the
     *                    default scheme is prepended.
     *
     * @return Resource The resource at this URI.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws InvalidUriException If URI is invalid.
     * @throws InvalidPathException If the path part of the URI is invalid.
     * @throws UnsupportedSchemeException If the URI scheme is not supported.
     */
    public function get($uri)
    {
        $parts = Uri::parse($uri);

        if ('' === $parts['scheme']) {
            $parts['scheme'] = $this->defaultScheme;
        }

        return $this->getRepository($parts['scheme'])->get($parts['path']);
    }

    /**
     * Returns the resources matching the given URI.
     *
     * @param string $uri A URI that may contain wildcards. If a path is passed,
     *                    the default scheme is prepended.
     *
     * @return ResourceCollection The resources matching the URI.
     *
     * @throws InvalidUriException If URI is invalid.
     * @throws InvalidPathException If the path part of the URI is invalid.
     * @throws UnsupportedSchemeException If the URI scheme is not supported.
     */
    public function find($uri)
    {
        $parts = Uri::parse($uri);

        if ('' === $parts['scheme']) {
            $parts['scheme'] = $this->defaultScheme;
        }

        return $this->getRepository($parts['scheme'])->find($parts['path']);
    }

    /**
     * Returns whether any resources match the given URI.
     *
     * @param string $uri A URI that may contain wildcards. If a path is passed,
     *                    the default scheme is prepended.
     *
     * @return bool Returns whether any resources exist that match the URI.
     *
     * @throws InvalidUriException If URI is invalid.
     * @throws InvalidPathException If the path part of the URI is invalid.
     * @throws UnsupportedSchemeException If the URI scheme is not supported.
     */
    public function contains($uri)
    {
        $parts = Uri::parse($uri);

        if ('' === $parts['scheme']) {
            $parts['scheme'] = $this->defaultScheme;
        }

        return $this->getRepository($parts['scheme'])->contains($parts['path']);
    }

    /**
     * Lists the directory entries of the given URI.
     *
     * @param string $uri The URI to the resource. If a path is passed, the
     *                    default scheme is prepended.
     *
     * @return ResourceCollection The directory entries.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     * @throws NoDirectoryException If the resource is no directory.
     * @throws InvalidUriException If URI is invalid.
     * @throws InvalidPathException If the path part of the URI is invalid.
     * @throws UnsupportedSchemeException If the URI scheme is not supported.
     */
    public function listDirectory($uri)
    {
        $parts = Uri::parse($uri);

        if ('' === $parts['scheme']) {
            $parts['scheme'] = $this->defaultScheme;
        }

        return $this->getRepository($parts['scheme'])->listDirectory($parts['path']);
    }

    /**
     * If necessary constructs and returns the repository for the given scheme.
     *
     * @param string $scheme A URI scheme.
     *
     * @return ResourceRepository The resource repository.
     *
     * @throws RepositoryFactoryException If the callable did not return an
     *                                    instance of {@link ResourceRepository}.
     * @throws UnsupportedSchemeException If the scheme is not supported.
     */
    private function getRepository($scheme)
    {
        if (!isset($this->repos[$scheme])) {
            throw new UnsupportedSchemeException(sprintf(
                'The scheme "%s" is not supported.',
                $scheme
            ));
        }

        if (is_callable($this->repos[$scheme])) {
            $callable = $this->repos[$scheme];
            $result = $callable($scheme);

            if (!$result instanceof ResourceRepository) {
                throw new RepositoryFactoryException(sprintf(
                    'The value of type "%s" returned by the locator factory '.
                    'registered for scheme "%s" does not implement '.
                    '"\Puli\Repository\ResourceRepository".',
                    gettype($result),
                    $scheme
                ));
            }

            $this->repos[$scheme] = $result;
        }

        return $this->repos[$scheme];
    }
}
