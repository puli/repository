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

use Webmozart\Puli\Locator\DataStorageInterface;
use Webmozart\Puli\Tag\TagInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyFileResource implements ResourceInterface
{
    /**
     * @var DataStorageInterface
     */
    protected $storage;

    /**
     * @var string
     */
    protected $repositoryPath;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var string[]
     */
    protected $alternativePaths;

    /**
     * @var TagInterface[]
     */
    protected $tags;

    public function __construct(DataStorageInterface $storage, $repositoryPath, $path = null)
    {
        $this->storage = $storage;
        $this->repositoryPath = $repositoryPath;
        $this->name = basename($this->repositoryPath);
        $this->path = $path;
    }

    public function __toString()
    {
        return $this->repositoryPath;
    }

    public function getRepositoryPath()
    {
        return $this->repositoryPath;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function overridePath($path)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    public function getAlternativePaths()
    {
        if (null === $this->alternativePaths) {
            $this->alternativePaths = $this->storage->getAlternativePaths($this->repositoryPath);
            $this->alternativePaths[] = $this->path;
        }

        return $this->alternativePaths;
    }

    public function addTag(TagInterface $tag)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    public function removeTag(TagInterface $tag)
    {
        throw new \BadMethodCallException(
            'Resources fetched from a resource locator may not be modified.'
        );
    }

    /**
     * @return TagInterface[]
     */
    public function getTags()
    {
        if (null === $this->tags) {
            $this->tags = $this->storage->getTags($this->repositoryPath);
        }

        return $this->tags;
    }
}
