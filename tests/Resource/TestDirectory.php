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

use Puli\Repository\Resource\AbstractResource;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestDirectory extends AbstractResource
{
    /**
     * @var Resource[]
     */
    private $entries = array();

    public function __construct($path = null, array $entries = array())
    {
        parent::__construct($path);

        foreach ($entries as $entry) {
            $this->entries[$entry->getName()] = $entry;
        }
    }

    public function getChild($relPath)
    {
        return $this->entries[$relPath];
    }

    public function hasChild($relPath)
    {
        return isset($this->entries[$relPath]);
    }

    public function hasChildren()
    {
        return count($this->entries) > 0;
    }

    public function listChildren()
    {
        return new ArrayResourceCollection($this->entries);
    }
}
