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

use Puli\Repository\Api\Resource\DirectoryResource;
use Puli\Repository\Resource\AbstractResource;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestDirectory extends AbstractResource implements DirectoryResource
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

    public function get($name, $version = null)
    {
        return $this->entries[$name];
    }

    public function contains($name)
    {
        return isset($this->entries[$name]);
    }

    public function listEntries()
    {
        return new ArrayResourceCollection($this->entries);
    }

    public function count($deep = false)
    {
        $count = count($this->entries);

        if ($deep) {
            foreach ($this->entries as $entry) {
                if ($entry instanceof DirectoryResource) {
                    $count += $entry->count(true);
                }
            }
        }

        return $count;
    }
}
