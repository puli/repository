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

use InvalidArgumentException;
use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;

/**
 * ChangeStream stored in memory (as an array).
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class InMemoryChangeStream implements ChangeStream
{
    /**
     * @var array
     */
    private $stack = array();

    /**
     * {@inheritdoc}
     */
    public function append(PuliResource $resource)
    {
        if (!isset($this->stack[$resource->getPath()])) {
            $this->stack[$resource->getPath()] = array();
        }

        $this->stack[$resource->getPath()][] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function buildStack(ResourceRepository $repository, $path)
    {
        if (!isset($this->stack[$path])) {
            throw new InvalidArgumentException(sprintf('No version of path %s were found in the ChangeStream', $path));
        }

        $resources = array();

        foreach ($this->stack[$path] as $resource) {
            $resource->attachTo($repository, $path);
            $resources[] = $resource;
        }

        return new ResourceStack($resources);
    }
}
