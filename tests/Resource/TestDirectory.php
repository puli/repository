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
use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Resource\DirectoryResourceInterface;
use Puli\Repository\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestDirectory extends AbstractResource implements DirectoryResourceInterface
{
    /**
     * @var ResourceInterface[]
     */
    private $entries = array();

    private $overrides;

    public function __construct($path = null, array $entries = array())
    {
        parent::__construct($path);

        foreach ($entries as $entry) {
            $this->entries[$entry->getName()] = $entry;
        }
    }

    public function get($name)
    {
        return $this->entries[$name];
    }

    public function contains($name)
    {
        return isset($this->entries[$name]);
    }

    public function listEntries()
    {
        return new ResourceCollection($this->entries);
    }

    public function override(ResourceInterface $resource)
    {
        $this->overrides = $resource;
    }

    public function getOverriddenResource()
    {
        return $this->overrides;
    }
}
