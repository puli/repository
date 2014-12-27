<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

use Puli\Repository\Assert\Assertion;
use Puli\Repository\ResourceRepository;

/**
 * Base class for resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResource implements Resource
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $repoPath;

    /**
     * @var int
     */
    private $version;

    /**
     * Creates a new resource.
     *
     * @param string|null $path    The path of the resource.
     * @param int         $version The resource version.
     */
    public function __construct($path = null, $version = 1)
    {
        Assertion::integer($version, 'The version must be an integer. Got: %2$s');
        Assertion::min($version, 1, 'The version must be 1 or higher. Got: "%s"');

        $this->path = $path;
        $this->repoPath = $path;
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->path ? basename($this->path) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function attachTo(ResourceRepository $repo, $path = null, $version = 1)
    {
        $this->repo = $repo;
        $this->version = $version;

        if (null !== $path) {
            $this->path = $path;
            $this->repoPath = $path;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $this->repo = null;
        $this->version = 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryPath()
    {
        return $this->repoPath;
    }

    /**
     * {@inheritdoc}
     */
    public function isAttached()
    {
        return null !== $this->repo;
    }

    /**
     * {@inheritdoc}
     */
    public function createReference($path)
    {
        $ref = clone $this;
        $ref->path = $path;

        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function isReference()
    {
        return $this->path !== $this->repoPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $data = array();

        $this->preSerialize($data);

        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($string)
    {
        $data = unserialize($string);

        $this->postUnserialize($data);
    }

    /**
     * Invoked before serializing a resource.
     *
     * Override this method if you want to serialize custom data in subclasses.
     *
     * @param array $data The data to serialize. Add custom data at the end of
     *                    the array.
     */
    protected function preSerialize(array &$data)
    {
        $data[] = $this->path;
        $data[] = $this->repoPath;
        $data[] = $this->version;
    }

    /**
     * Invoked after unserializing a resource.
     *
     * Override this method if you want to unserialize custom data in
     * subclasses.
     *
     * @param array $data The unserialized data. Pop your custom data from the
     *                    end of the array before calling the parent method.
     */
    protected function postUnserialize(array $data)
    {
        $this->version = array_pop($data);
        $this->repoPath = array_pop($data);
        $this->path = array_pop($data);
    }
}
