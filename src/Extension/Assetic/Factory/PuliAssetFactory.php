<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Assetic\Factory;

use Assetic\Factory\AssetFactory;
use Webmozart\Puli\Extension\Assetic\Asset\PuliAsset;
use Webmozart\Puli\Filesystem\Resource\LocalResourceInterface;
use Webmozart\Puli\ResourceRepositoryInterface;
use Webmozart\Puli\Uri\UriRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactory extends AssetFactory
{
    /**
     * @var ResourceRepositoryInterface
     */
    private $repo;

    public function __construct(ResourceRepositoryInterface $repo, $debug = false)
    {
        parent::__construct('', $debug);

        $this->repo = $repo;
    }

    protected function parseInput($input, array $options = array())
    {
        if ('@' == $input[0]) {
            return $this->createAssetReference(substr($input, 1));
        }

        if (0 === strpos($input, '//')) {
            return $this->createHttpAsset($input, $options['vars']);
        }

        if (false !== ($offset = strpos($input, '://'))) {
            $scheme = substr($input, 0, $offset);
            $knownScheme = $this->repo instanceof UriRepositoryInterface
                && in_array($scheme, $this->repo->getSupportedSchemes());

            if (!$knownScheme) {
                return $this->createHttpAsset($input, $options['vars']);
            }
        // Don't execute is_file() for URIs -> elseif
        } elseif (is_file($input)) {
            return $this->createFileAsset($input, null, null, $options['vars']);
        }

        $resources = $this->repo->find($input);

        if (1 === count($resources)) {
            return $this->createPuliAsset($resources[0], $options['vars']);
        }

        $assets = array();

        foreach ($resources as $entry) {
            $assets[] = $this->createPuliAsset($entry, array());
        }

        return $this->createAssetCollection($assets, $options);
    }

    protected function createPuliAsset(LocalResourceInterface $resource, array $vars)
    {
        return new PuliAsset($resource->getPath(), $resource->getLocalPath(), array(), $vars);
    }
}
