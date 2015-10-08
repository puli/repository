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
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * A link resource targeting to another resource.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LinkResource extends GenericResource implements PuliResource
{
    /**
     * @var string
     */
    private $targetPath;

    /**
     * @param string      $targetPath
     * @param string|null $path
     */
    public function __construct($targetPath, $path = null)
    {
        parent::__construct($path);

        $this->targetPath = $targetPath;
    }

    /**
     * @return string
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget()
    {
        if (!$this->getRepository()) {
            throw ResourceNotFoundException::forPath($this->getTargetPath());
        }

        return $this->getRepository()->get($this->getTargetPath());
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($relPath)
    {
        if (!$this->getRepository()) {
            throw ResourceNotFoundException::forPath($this->getTargetPath().'/'.$relPath);
        }

        return $this->getRepository()->get($this->getTargetPath().'/'.$relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChild($relPath)
    {
        if (!$this->getRepository()) {
            return false;
        }

        return $this->getRepository()->contains($this->getTargetPath().'/'.$relPath);
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

        foreach ($this->getRepository()->listChildren($this->getTargetPath()) as $child) {
            $children[$child->getName()] = $child;
        }

        return $children;
    }
}
