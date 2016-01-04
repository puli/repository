<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\ChangeStream;

use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\Api\ChangeStream\VersionList;
use Puli\Repository\Api\NoVersionFoundException;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonEncoder;

/**
 * A change stream backed by a JSON file.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonChangeStream implements ChangeStream
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $json;

    /**
     * @var JsonEncoder
     */
    private $encoder;

    /**
     * @param string $path The path to the JSON file.
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->encoder = new JsonEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function append(PuliResource $resource)
    {
        if (null === $this->json) {
            $this->load();
        }

        if (!isset($this->json[$resource->getPath()])) {
            $this->json[$resource->getPath()] = array();
        }

        $this->json[$resource->getPath()][] = serialize($resource);

        $this->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function purge($path)
    {
        if (null === $this->json) {
            $this->load();
        }

        unset($this->json[$path]);

        $this->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (null === $this->json) {
            $this->load();
        }

        $this->json = array();

        $this->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function contains($path)
    {
        if (null === $this->json) {
            $this->load();
        }

        return isset($this->json[$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions($path, ResourceRepository $repository = null)
    {
        if (null === $this->json) {
            $this->load();
        }

        if (!isset($this->json[$path])) {
            throw NoVersionFoundException::forPath($path);
        }

        $versions = array();

        foreach ($this->json[$path] as $resource) {
            $resource = unserialize($resource);

            if (null !== $repository) {
                $resource->attachTo($repository, $path);
            }

            $versions[] = $resource;
        }

        return new VersionList($path, $versions);
    }

    /**
     * Loads the JSON file.
     */
    private function load()
    {
        $decoder = new JsonDecoder();
        $decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        $this->json = file_exists($this->path)
            ? $decoder->decodeFile($this->path)
            : array();
    }

    /**
     * Writes the JSON file.
     */
    private function flush()
    {
        $this->encoder->encodeFile($this->json, $this->path);
    }
}
