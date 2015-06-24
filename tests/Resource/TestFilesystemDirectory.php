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

use Puli\Repository\Resource\AbstractFilesystemResource;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class TestFilesystemDirectory extends AbstractFilesystemResource
{
    /**
     * @var Resource[]
     */
    private $children = array();

    private $metadata;

    public function __construct($path = null, array $children = array())
    {
        parent::__construct(null, $path);

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
