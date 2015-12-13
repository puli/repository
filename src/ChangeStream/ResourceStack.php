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
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
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
class ResourceStack extends ArrayResourceCollection
{
    /**
     * Get the last version from the stack.
     *
     * @return PuliResource
     */
    public function getCurrentVersion()
    {
        $stack = $this->toArray();

        if (!is_array($stack) || 0 === count($stack)) {
            throw new RuntimeException('Impossible to find the current version of an empty stack.');
        }

        return end($stack);
    }

    /**
     * Get the first version from the stack.
     *
     * @return PuliResource
     */
    public function getFirstVersion()
    {
        $stack = $this->toArray();

        if (!is_array($stack) || 0 === count($stack)) {
            throw new RuntimeException('Impossible to find the first version of an empty stack.');
        }

        return reset($stack);
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
        $stack = $this->toArray();

        if (!is_array($stack) || 0 === count($stack)) {
            throw new RuntimeException(sprintf('Impossible to find the version %s of an empty stack.', $versionNumber));
        }

        Assert::oneOf($versionNumber, $this->getAvailableVersions(), 'Impossible to find version %s (stack: %s).');

        return $stack[$versionNumber];
    }

    /**
     * Get an array of the available versions of this resource.
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        return $this->keys();
    }
}
