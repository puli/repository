<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api\ChangeStream;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Puli\Repository\Api\Resource\PuliResource;
use Webmozart\Assert\Assert;

/**
 * Contains different versions of a resource.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VersionList implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $versions;

    /**
     * Creates a new version list.
     *
     * @param string         $path     The Puli path.
     * @param PuliResource[] $versions The versions of the resource, starting
     *                                 with the first.
     */
    public function __construct($path, array $versions)
    {
        Assert::stringNotEmpty($path, 'The Puli path must be a non-empty string. Got: %s');
        Assert::allIsInstanceOf($versions, 'Puli\Repository\Api\Resource\PuliResource');
        Assert::greaterThanEq(count($versions), 1, 'Expected at least one version.');

        $this->path = $path;
        $this->versions = array_values($versions);
    }

    /**
     * Returns the path of the versioned resources.
     *
     * @return string The Puli path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the current version of the resource.
     *
     * @return PuliResource The current version.
     */
    public function getCurrent()
    {
        return $this->get($this->getCurrentVersion());
    }

    /**
     * Returns the current version number.
     *
     * @return int The current version number.
     */
    public function getCurrentVersion()
    {
        return count($this->versions) - 1;
    }

    /**
     * Returns the first version of the resource.
     *
     * @return PuliResource The first version.
     */
    public function getFirst()
    {
        return $this->get($this->getFirstVersion());
    }

    /**
     * Returns the first version number.
     *
     * @return int The first version number.
     */
    public function getFirstVersion()
    {
        return 0;
    }

    /**
     * Returns whether a specific version exists.
     *
     * @param int $version The version number starting at 0.
     *
     * @return bool Whether the version exists.
     */
    public function contains($version)
    {
        return isset($this->versions[$version]);
    }

    /**
     * Returns a specific version of the resource.
     *
     * @param int $version The version number starting at 0.
     *
     * @return PuliResource The resource.
     *
     * @throws OutOfBoundsException If the version number does not exist.
     */
    public function get($version)
    {
        if (!isset($this->versions[$version])) {
            throw new OutOfBoundsException(sprintf(
                'The version %s of path %s does not exist.',
                $version,
                $this->path
            ));
        }

        return $this->versions[$version];
    }

    /**
     * Returns all version numbers.
     *
     * @return int[] The version numbers.
     */
    public function getVersions()
    {
        return array_keys($this->versions);
    }

    /**
     * Returns the list as array indexed by version numbers.
     *
     * @return PuliResource[] The resource versions indexed by their version numbers.
     */
    public function toArray()
    {
        return $this->versions;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->versions);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->contains($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('List entries may not be changed.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('List entries may not be removed.');
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->versions);
    }
}
