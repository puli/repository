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

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\ChangeStream\Normalizer\DirectoryResourceNormalizer;
use Puli\Repository\ChangeStream\Normalizer\FileResourceNormalizer;
use Puli\Repository\ChangeStream\Normalizer\GenericResourceNormalizer;
use Puli\Repository\ChangeStream\Normalizer\LinkResourceNormalizer;
use Puli\Repository\ChangeStream\Normalizer\ResourceNormalizer;

/**
 * Stream to track repositories changes and fetch previous versions of resources.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ChangeStream
{
    /**
     * @var array
     */
    private $stack;

    /**
     * @var ResourceNormalizer[]
     */
    private $normalizers;

    /**
     * Create a change stream.
     */
    public function __construct()
    {
        $this->stack = array();

        $this->normalizers = array(
            new LinkResourceNormalizer(),
            new DirectoryResourceNormalizer(),
            new FileResourceNormalizer(),
            new GenericResourceNormalizer(),
        );
    }

    /**
     * Store a version of a resource in the ChangeStream to retrieve it if needed.
     *
     * @param string       $path
     * @param PuliResource $resource
     */
    public function log($path, PuliResource $resource)
    {
        if (!array_key_exists($path, $this->stack)) {
            $this->stack[$path] = array();
        }

        $this->stack[$path][] = $this->normalize($resource);
    }

    /**
     * Create a stack of resources for the given path.
     *
     * @param ResourceRepository $repository
     * @param string             $path
     *
     * @return ResourceStack
     */
    public function buildResourceStack(ResourceRepository $repository, $path)
    {
        $stack = array();

        if (isset($this->stack[$path]) && is_array($this->stack[$path])) {
            foreach ($this->stack[$path] as $data) {
                $resource = $this->denormalize($data);
                $resource->attachTo($repository, $path);

                $stack[] = $resource;
            }
        }

        return new ResourceStack($stack);
    }

    /**
     * @return array
     */
    public function getLogStack()
    {
        return $this->stack;
    }

    /**
     * Register a given normalizer for usage by this change stream.
     *
     * @param ResourceNormalizer $normalizer
     */
    public function registerNormalizer(ResourceNormalizer $normalizer)
    {
        array_unshift($this->normalizers, $normalizer);
    }

    /**
     * @param PuliResource $resource
     *
     * @return array
     */
    private function normalize(PuliResource $resource)
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($resource)) {
                $data = $normalizer->normalize($resource);
                $data['normalizer'] = get_class($normalizer);

                return $data;
            }
        }

        throw new \RuntimeException(sprintf(
            'Instances of "%s" are not supported by this ChangeStream as no normalizer in the '.
            'ChangeStream supports them (see the documentation to create your own normalizer).',
            get_class($resource)
        ));
    }

    /**
     * @param array $data
     *
     * @return PuliResource
     */
    private function denormalize($data)
    {
        foreach ($this->normalizers as $normalizer) {
            if (get_class($normalizer) === $data['normalizer']) {
                return $normalizer->denormalize($data);
            }
        }

        throw new \RuntimeException(sprintf(
            'Normalizer %s is not registered in this ChangeStream. You need to register it to be able '.
            'to get versions of resource "%s".',
            $data['normalizer'],
            $data['path']
        ));
    }
}
