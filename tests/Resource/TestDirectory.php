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
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestDirectory extends GenericResource
{
    /**
     * @var PuliResource[]
     */
    private $children = array();

    private $metadata;

    public function __construct($path = null, array $children = array())
    {
        parent::__construct($path);

        foreach ($children as $child) {
            $this->children[$child->getName()] = $child;
        }

        $this->metadata = new TestMetadata();
    }

    public function getChild($relPath)
    {
        return $this->children[$relPath];
    }

    public function hasChild($relPath)
    {
        return isset($this->children[$relPath]);
    }

    public function hasChildren()
    {
        return count($this->children) > 0;
    }

    public function listChildren()
    {
        return new ArrayResourceCollection($this->children);
    }

    public function getMetadata()
    {
        return $this->metadata;
    }
}
