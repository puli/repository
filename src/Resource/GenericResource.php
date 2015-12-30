<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\Resource\ResourceMetadata;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\ChangeStream\ResourceStack;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * A generic resource.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GenericResource implements PuliResource
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $repoPath;

    /**
     * Creates a new resource.
     *
     * @param string|null $path The path of the resource.
     */
    public function __construct($path = null)
    {
        $this->path = $path;
        $this->repoPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->path ? basename($this->path) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($relPath)
    {
        if (!$this->getRepository()) {
            throw ResourceNotFoundException::forPath($this->getRepositoryPath().'/'.$relPath);
        }

        return $this->getRepository()->get($this->getRepositoryPath().'/'.$relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChild($relPath)
    {
        if (!$this->getRepository()) {
            return false;
        }

        return $this->getRepository()->contains($this->getRepositoryPath().'/'.$relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        if (!$this->getRepository()) {
            return false;
        }

        return $this->getRepository()->hasChildren($this->getRepositoryPath());
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren()
    {
        $children = new ArrayResourceCollection();

        if (!$this->getRepository()) {
            return $children;
        }

        foreach ($this->getRepository()->listChildren($this->getRepositoryPath()) as $child) {
            $children[$child->getName()] = $child;
        }

        return $children;
    }

    /**
     * {@inheritdoc}
     */
    public function getStack()
    {
        if (!$this->getRepository()) {
            return new ResourceStack(array($this));
        }

        return $this->getRepository()->getStack($this->getRepositoryPath());
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return new ResourceMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function attachTo(ResourceRepository $repo, $path = null)
    {
        $this->repo = $repo;

        if (null !== $path) {
            $this->path = $path;
            $this->repoPath = $path;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $this->repo = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryPath()
    {
        return $this->repoPath;
    }

    /**
     * {@inheritdoc}
     */
    public function isAttached()
    {
        return null !== $this->repo;
    }

    /**
     * {@inheritdoc}
     */
    public function createReference($path)
    {
        $ref = clone $this;
        $ref->path = $path;

        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function isReference()
    {
        return $this->path !== $this->repoPath;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $data = array();

        $this->preSerialize($data);

        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($string)
    {
        $data = unserialize($string);

        $this->postUnserialize($data);
    }

    /**
     * Invoked before serializing a resource.
     *
     * Override this method if you want to serialize custom data in subclasses.
     *
     * @param array $data The data to serialize. Add custom data at the end of
     *                    the array.
     */
    protected function preSerialize(array &$data)
    {
        $data[] = $this->path;
        $data[] = $this->repoPath;
    }

    /**
     * Invoked after unserializing a resource.
     *
     * Override this method if you want to unserialize custom data in
     * subclasses.
     *
     * @param array $data The unserialized data. Pop your custom data from the
     *                    end of the array before calling the parent method.
     */
    protected function postUnserialize(array $data)
    {
        $this->repoPath = array_pop($data);
        $this->path = array_pop($data);
    }
}
