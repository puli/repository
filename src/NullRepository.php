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

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * A repository that does nothing.
 *
 * This repository can be used if you need to inject a repository instance in
 * some code, but you don't want that repository to do anything (for example
 * in tests).
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NullRepository implements EditableRepository
{
    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        throw ResourceNotFoundException::forPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getStack($path)
    {
        throw ResourceNotFoundException::forPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        return new ArrayResourceCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        return new ArrayResourceCollection();
    }
}
