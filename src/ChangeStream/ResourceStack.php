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

use OutOfBoundsException;
use Puli\Repository\Api\Resource\PuliResource;
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
    public function __construct(array $stack)
    {
        Assert::allIsInstanceOf($stack, 'Puli\Repository\Api\Resource\PuliResource');
        Assert::greaterThanEq(count($stack), 1, 'A ResourceStack cannot be empty');

        $this->stack = array_values($stack);
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
     * @return int
     */
    public function getCurrentVersion()
    {
        return count($this->stack) - 1;
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
     * @return int
     */
    public function getFirstVersion()
    {
        return 0;
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
        if (!isset($this->stack[$version])) {
            throw new OutOfBoundsException('Could not retrieve the version %s (stack: %s).');
        }

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
