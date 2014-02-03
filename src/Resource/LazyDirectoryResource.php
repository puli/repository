<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyDirectoryResource extends LazyFileResource implements \IteratorAggregate, DirectoryResourceInterface
{
    private $entries;

    public function get($name)
    {

    }

    public function contains($name)
    {

    }

    public function all()
    {
        if (null === $this->entries) {
            $this->entries = $this->storage->getDirectoryEntries($this->repositoryPath);
        }

        return $this->entries;
    }

    public function add(ResourceInterface $resource)
    {

    }

    public function remove($name)
    {

    }

    public function offsetExists($offset)
    {

    }

    public function offsetGet($offset)
    {

    }

    public function offsetSet($offset, $value)
    {

    }

    public function offsetUnset($offset)
    {

    }

    public function count()
    {

    }

    public function getIterator()
    {

    }
}
