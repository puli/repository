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
    public function append($path, PuliResource $resource)
    {
        if (!array_key_exists($path, $this->stack)) {
            $this->stack[$path] = array();
        }

        $this->stack[$path][] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function buildStack(ResourceRepository $repository, $path)
    {
        if (isset($this->stack[$path])) {
            return new ResourceStack($this->stack[$path]);
        }

        return new ResourceStack(array());
    }
}
