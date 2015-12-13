<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class TestNullResource implements PuliResource
{
    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($relPath)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasChild($relPath)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getStack()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryPath()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function attachTo(ResourceRepository $repo, $path = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isAttached()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createReference($path)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isReference()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
    }
}
