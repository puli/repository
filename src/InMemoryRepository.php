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

use ArrayIterator;
use InvalidArgumentException;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\NoDirectoryException;
use Puli\Repository\Api\Resource\DirectoryResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Iterator\RegexIterator;
use Puli\Repository\Iterator\SelectorIterator;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\VirtualDirectoryResource;
use Puli\Repository\Selector\Selector;
use Webmozart\PathUtil\Path;

/**
 * An in-memory resource repository.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/css', new LocalDirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * Alternatively, another repository can be passed as "backend". The paths of
 * this backend can be passed to the second argument of {@link add()}. By
 * default, a {@link FilesystemRepository} is used:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/css', '/path/to/project/res/css');
 * ```
 *
 * You can also create the backend manually and pass it to the constructor:
 *
 * ```php
 * use Puli\Repository\FilesystemRepository;
 * use Puli\Repository\InMemoryRepository;
 *
 * $backend = new FilesystemRepository('/path/to/project');
 *
 * $repo = new InMemoryRepository($backend)
 * $repo->add('/css', '/res/css');
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryRepository implements EditableRepository
{
    /**
     * @var Resource[]|\Puli\Repository\Api\\Puli\Repository\Api\Resource\DirectoryResource[]
     */
    private $resources = array();

    /**
     * @var Resource[][]
     */
    private $versions = array();

    /**
     * @var ResourceRepository
     */
    private $backend;

    /**
     * Creates a new repository.
     *
     * The backend repository is used to lookup the paths passed to the
     * second argument of {@link add}. If none is passed, a
     * {@link FilesystemRepository} will be used.
     *
     * @param ResourceRepository $backend The backend repository.
     *
     * @see ResourceRepository
     */
    public function __construct(ResourceRepository $backend = null)
    {
        $this->backend = $backend ?: new FilesystemRepository();
        $this->resources['/'] = new VirtualDirectoryResource('/');
        $this->resources['/']->attachTo($this);
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, $version = null)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (!isset($this->resources[$path])) {
            throw ResourceNotFoundException::forPath($path);
        }

        if (null === $version) {
            return $this->resources[$path];
        }

        if (!isset($this->versions[$path][$version])) {
            throw ResourceNotFoundException::forVersion($version, $path);
        }

        return $this->versions[$path][$version];
    }

    /**
     * {@inheritdoc}
     */
    public function find($selector)
    {
        Assertion::selector($selector);

        $selector = Path::canonicalize($selector);
        $resources = array();

        if (Selector::isSelector($selector)) {
            $resources = iterator_to_array(new SelectorIterator(
                $selector,
                new ArrayIterator($this->resources)
            ));
        } elseif (isset($this->resources[$selector])) {
            $resources = array($this->resources[$selector]);
        }

        return new ArrayResourceCollection($resources);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        Assertion::selector($selector);

        $selector = Path::canonicalize($selector);

        if (Selector::isSelector($selector)) {
            $iterator = new SelectorIterator(
                $selector,
                new ArrayIterator($this->resources)
            );
            $iterator->rewind();

            return $iterator->valid();
        }

        return isset($this->resources[$selector]);
    }

    /**
     * {@inheritdoc}
     *
     * If a path is passed as second argument, the added resources are fetched
     * from the backend passed to {@link __construct}.
     *
     * @param string                             $path     The path at which to
     *                                                     add the resource.
     * @param string|Resource|\Puli\Repository\Api\ResourceCollection $resource The resource(s) to
     *                                                     add at that path.
     *
     * @throws InvalidArgumentException If the path is invalid. The path must be
     *                                  a non-empty string starting with "/".
     * @throws UnsupportedResourceException If the resource is invalid.
     */
    public function add($path, $resource)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (is_string($resource)) {
            // Use find() only if the string is actually a selector. We want
            // deterministic results when using a selector, even if the selector
            // just matches one result.
            // See https://github.com/puli/puli/issues/17
            if (Selector::isSelector($resource)) {
                $resource = $this->backend->find($resource);
            } else {
                $resource = $this->backend->get($resource);
            }
        }

        if ($resource instanceof ResourceCollection) {
            $this->ensureDirectoryExists($path);
            foreach ($resource as $entry) {
                $this->addResource($path.'/'.$entry->getName(), $entry);
            }

            // Keep the resources sorted by file name
            ksort($this->resources);

            return;
        }

        if ($resource instanceof Resource) {
            $this->ensureDirectoryExists(Path::getDirectory($path));
            $this->addResource($path, $resource);

            ksort($this->resources);

            return;
        }

        throw new UnsupportedResourceException(sprintf(
            'The passed resource must be a string, Resource or '.
            'ResourceCollection. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($selector)
    {
        Assertion::selector($selector);

        $selector = Path::canonicalize($selector);

        Assertion::notEq('/', $selector, 'The root directory cannot be removed.');

        $resourcesToRemove = array();
        $removed = 0;

        if (Selector::isSelector($selector)) {
            $resourcesToRemove = new SelectorIterator(
                $selector,
                new ArrayIterator($this->resources)
            );
        } elseif (isset($this->resources[$selector])) {
            $resourcesToRemove[] = $this->resources[$selector];
        }

        foreach ($resourcesToRemove as $resource) {
            $this->removeResource($resource, $removed);
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function listDirectory($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (!isset($this->resources[$path])) {
            throw ResourceNotFoundException::forPath($path);
        }

        if (!$this->resources[$path] instanceof DirectoryResource) {
            throw NoDirectoryException::forPath($path);
        }

        $staticPrefix = rtrim($path, '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        $resources = iterator_to_array(new RegexIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->resources)
        ));

        return new ArrayResourceCollection($resources);
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     *
     * @throws NoDirectoryException If a resource with that path exists, but is
     *                              no directory.
     */
    private function ensureDirectoryExists($path)
    {
        if (!isset($this->resources[$path])) {
            // Recursively initialize parent directories
            if ($path !== '/') {
                $this->ensureDirectoryExists(Path::getDirectory($path));
            }

            $this->resources[$path] = new VirtualDirectoryResource($path);
            $this->resources[$path]->attachTo($this);

            return;
        }

        if (!$this->resources[$path] instanceof DirectoryResource) {
            throw NoDirectoryException::forPath($path);
        }
    }

    private function addResource($path, Resource $resource)
    {
        // Don't modify resources attached to other repositories
        if ($resource->isAttached()) {
            $resource = clone $resource;
        }

        if (!isset($this->versions[$path])) {
            $this->versions[$path] = array();
        }

        $basePath = '/' === $path ? $path : $path.'/';
        $version = count($this->versions[$path]) + 1;
        $entries = $resource instanceof DirectoryResource ? $resource->listEntries() : array();

        // Attach resource to locator *after* calling listEntries(), because
        // this method usually depends on the previously attached repository
        $resource->attachTo($this, $path, $version);

        // Add the resource before adding nested resources, so that the
        // array stays sorted
        $this->resources[$path] = $resource;
        $this->versions[$path][$version] = $resource;

        // Recursively attach directory contents
        foreach ($entries as $name => $entry) {
            $this->addResource($basePath.$name, $entry);
        }
    }

    private function removeResource(Resource $resource, &$counter)
    {
        // Ignore non-existing resources
        if (!isset($this->resources[$resource->getPath()])) {
            return;
        }

        // Recursively register directory contents
        if ($resource instanceof DirectoryResource) {
            foreach ($this->listDirectory($resource->getPath()) as $entry) {
                $this->removeResource($entry, $counter);
            }
        }

        unset($this->resources[$resource->getPath()]);
        unset($this->versions[$resource->getPath()]);

        // Detach from locator
        $resource->detach($this);

        // Keep track of the number of removed resources
        ++$counter;
    }
}
