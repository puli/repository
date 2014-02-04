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

use Webmozart\Puli\Locator\ResourceNotFoundException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyDirectoryResource extends LazyFileResource implements \IteratorAggregate, DirectoryResourceInterface
{
    private $entries;

    public function get($name)
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        if (!isset($this->entries[$name])) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist in directory "%s".',
                $name,
                $this->repositoryPath
            ));
        }

        return $this->entries[$name];
    }

    public function contains($name)
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        return isset($this->entries[$name]);
    }

    public function all()
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        // Dismiss keys, otherwise users may rely on them and we can't change
        // the implementation anymore.
        return array_values($this->entries);
    }

    public function add(ResourceInterface $resource)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    public function remove($name)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    public function offsetExists($offset)
    {
        return $this->contains($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    public function count()
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        return count($this->entries);
    }

    public function getIterator()
    {
        if (null === $this->entries) {
            $this->loadEntries();
        }

        return new \ArrayIterator(array_values($this->entries));
    }

    private function loadEntries()
    {
        $entries = $this->storage->getDirectoryEntries($this->repositoryPath);

        $this->entries = array();

        foreach ($entries as $entry) {
            $this->entries[$entry->getName()] = $entry;
        }
    }
}
