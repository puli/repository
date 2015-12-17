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
 * Represents a versionned stack of resources for a given path.
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
     * Get the last version from the stack.
     *
     * @return PuliResource
     */
    public function getCurrentVersion()
    {
        if (0 === count($this->stack)) {
            throw new RuntimeException('Could not retrieve the current version of an empty stack.');
        }

        return end($this->stack);
    }

    /**
     * Get the first version from the stack.
     *
     * @return PuliResource
     */
    public function getFirstVersion()
    {
        if (0 === count($this->stack)) {
            throw new RuntimeException('Could not retrieve the first version of an empty stack.');
        }

        return reset($this->stack);
    }

    /**
     * Get a specific version from the stack.
     *
     * @param int $versionNumber The version number (first is 0).
     *
     * @return PuliResource
     */
    public function getVersion($versionNumber)
    {
        if (0 === count($this->stack)) {
            throw new RuntimeException(sprintf(
                'Could not retrieve the version %s of an empty stack.',
                $versionNumber
            ));
        }

        Assert::oneOf($versionNumber, $this->getAvailableVersions(), 'Could not retrieve the version %s (stack: %s).');

        return $this->stack[$versionNumber];
    }

    /**
     * Get an array of the available versions of this resource.
     *
     * @return array
     */
    public function getAvailableVersions()
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
