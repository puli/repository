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

use Webmozart\Puli\Tag\TagInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileResource implements ResourceInterface
{
    /**
     * @var string
     */
    protected $repositoryPath;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string[]
     */
    protected $alternativePaths = array();

    /**
     * @var \SplObjectStorage
     */
    protected $tags = array();

    public function __construct($repositoryPath, $path = null)
    {
        $this->repositoryPath = $repositoryPath;
        $this->name = basename($repositoryPath);
        $this->path = $path;
        $this->tags = new \SplObjectStorage();
    }

    /**
     * @return string
     */
    public function getRepositoryPath()
    {
        return $this->repositoryPath;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function overridePath($path)
    {
        if (null !== $this->path) {
            $this->alternativePaths[] = $this->path;
        }

        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function getAlternativePaths()
    {
        return $this->alternativePaths;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return iterator_to_array($this->tags);
    }

    public function addTag(TagInterface $tag)
    {
        $this->tags->attach($tag);
    }

    public function removeTag(TagInterface $tag)
    {
        $this->tags->detach($tag);
    }

    public function __toString()
    {
        return $this->repositoryPath;
    }
}
