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
 * @since  1.0
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
     * @var boolean[]
     */
    protected $tags = array();

    public function __construct($repositoryPath, $path = null)
    {
        $this->repositoryPath = $repositoryPath;
        $this->name = basename($repositoryPath);

        if (null !== $path) {
            $this->path = $path;
            $this->alternativePaths[] = $path;
        }
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
        $this->path = $path;
        $this->alternativePaths[] = $path;
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
        return array_keys($this->tags);
    }

    public function addTag($tag)
    {
        $this->tags[$tag] = true;
    }

    public function removeTag($tag)
    {
        unset($this->tags[$tag]);
    }

    public function __toString()
    {
        return $this->repositoryPath;
    }
}
