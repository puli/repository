<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Assetic;

use Assetic\Factory\AssetFactory;
use Webmozart\Puli\Extension\Assetic\Asset\PuliAsset;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Locator\UriLocatorInterface;
use Webmozart\Puli\Resource\ResourceCollectionInterface;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactory extends AssetFactory
{
    /**
     * @var ResourceLocatorInterface
     */
    private $locator;

    public function __construct(ResourceLocatorInterface $locator, $debug = false)
    {
        parent::__construct('', $debug);

        $this->locator = $locator;
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
            $knownScheme = $this->locator instanceof UriLocatorInterface
                && in_array($scheme, $this->locator->getRegisteredSchemes());

            if (!$knownScheme) {
                return $this->createHttpAsset($input, $options['vars']);
            }
        // Don't execute is_file() for URIs -> elseif
        } elseif (is_file($input)) {
            return $this->createFileAsset($input, null, null, $options['vars']);
        }

        $resource = $this->locator->get($input);

        if ($resource instanceof ResourceCollectionInterface) {
            $assets = array();

            foreach ($resource as $entry) {
                /** @var ResourceInterface $entry */
                $assets[] = $this->createPuliAsset($entry, array());
            }

            return $this->createAssetCollection($assets, $options);
        }

        return $this->createPuliAsset($resource->getRealPath(), $options['vars']);
    }

    protected function createPuliAsset(ResourceInterface $resource, array $vars)
    {
        return new PuliAsset($resource->getPath(), $resource->getRealPath(), array(), $vars);
    }
}
