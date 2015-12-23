<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\ChangeStream;

use Puli\Repository\Api\Resource\PuliResource;
use RuntimeException;
use Webmozart\Assert\Assert;

/**
 * Represents a versioned stack of resources for a given path.
 *
 * You can access different versions of a resource using a ChangeStream
 * that will return you resource stacks.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ResourceStack
{
    /**
     * @var array
     */
    private $stack;

    /**
     * @param array $stack
     */
    public function __construct($stack)
    {
        Assert::isArray($stack, 'Built resource stack must be an array.');

        $this->stack = $stack;
    }

    /**
     * Get the current version resource.
     *
     * @return PuliResource
     */
    public function getCurrent()
    {
        return $this->get($this->getCurrentVersion());
    }

    /**
     * Get the current version number.
     *
     * @return integer
     */
    public function getCurrentVersion()
    {
        $versions = $this->getVersions();

        if (0 === count($versions)) {
            throw new RuntimeException('Could not retrieve the current version of an empty stack.');
        }

        return end($versions);
    }

    /**
     * Get the current version resource.
     *
     * @return PuliResource
     */
    public function getFirst()
    {
        return $this->get($this->getFirstVersion());
    }

    /**
     * Get the first version number.
     *
     * @return integer
     */
    public function getFirstVersion()
    {
        $versions = $this->getVersions();

        if (0 === count($versions)) {
            throw new RuntimeException('Could not retrieve the first version of an empty stack.');
        }

        return reset($versions);
    }

    /**
     * Get a specific version resource from the stack by its version number.
     *
     * @param int $version The version number (first is 0).
     *
     * @return PuliResource
     */
    public function get($version)
    {
        $versions = $this->getVersions();

        if (0 === count($versions)) {
            throw new RuntimeException(sprintf(
                'Could not retrieve the version %s of an empty stack.',
                $version
            ));
        }

        Assert::oneOf($version, $versions, 'Could not retrieve the version %s (stack: %s).');

        return $this->stack[$version];
    }

    /**
     * Get an array of the available versions of this resource.
     *
     * @return array
     */
    public function getVersions()
    {
        return array_keys($this->stack);
    }

    /**
     * Returns the stack contents as array.
     *
     * @return PuliResource[] The resources in the stack.
     */
    public function toArray()
    {
        return $this->stack;
    }
}
