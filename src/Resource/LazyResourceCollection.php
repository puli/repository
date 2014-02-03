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

use Webmozart\Puli\Locator\ResourceLocatorInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyResourceCollection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var string[]|\Webmozart\Puli\Resource\ResourceInterface[]
     */
    private $resources;

    /**
     * @var ResourceLocatorInterface
     */
    private $locator;

    private $cursor = 0;

    public function __construct(ResourceLocatorInterface $locator, array $repositoryPaths)
    {
        $this->resources = $repositoryPaths;
        $this->locator = $locator;
    }

    public function offsetExists($offset)
    {
        return isset($this->resources[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->resources[$offset])) {
            throw new \OutOfBoundsException(sprintf(
                'The offset "%s" does not exist.',
                $offset
            ));
        }

        if (!$this->resources[$offset] instanceof ResourceInterface) {
            $this->resources[$offset] = $this->locator->get($this->resources[$offset]);
        }

        return $this->resources[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections may not be modified.'
        );
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections may not be modified.'
        );
    }

    public function count()
    {
        return count($this->resources);
    }

    public function current()
    {
        return $this->offsetGet($this->cursor);
    }

    public function next()
    {
        ++$this->cursor;
    }

    public function key()
    {
        return $this->cursor;
    }

    public function valid()
    {
        return $this->cursor < count($this->resources);
    }

    public function rewind()
    {
        $this->cursor = 0;
    }
}
