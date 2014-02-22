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
class DirectoryResource extends FileResource implements \IteratorAggregate, DirectoryResourceInterface
{
    private $entries = array();

    public function add(ResourceInterface $resource)
    {
        $parentPath = dirname($resource->getPath());

        // Fix root directory on Windows
        if ('\\' === $parentPath) {
            $parentPath = '/';
        }

        if ($this->repositoryPath !== $parentPath) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot add resource "%s" to the directory "%s", since it is '.
                'located in a different directory.',
                $parentPath,
                $this->repositoryPath
            ));
        }

        $this->entries[basename($resource->getPath())] = $resource;

        ksort($this->entries);
    }

    public function get($name)
    {
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
        return isset($this->entries[$name]);
    }

    public function remove($name)
    {
        if (!isset($this->entries[$name])) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist in directory "%s".',
                $name,
                $this->repositoryPath
            ));
        }

        unset($this->entries[$name]);
    }

    public function all()
    {
        // Dismiss keys, otherwise users may rely on them and we can't change
        // the implementation anymore.
        return new ResourceCollection(array_values($this->entries));
    }

    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->entries));
    }

    public function count()
    {
        return count($this->entries);
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
        $this->add($value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
