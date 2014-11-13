<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Filesystem\Resource;

use Puli\Repository\UnsupportedResourceException;
use Puli\Resource\Collection\ResourceCollection;
use Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceCollection extends ResourceCollection
{
    public function add(ResourceInterface $resource)
    {
        if (!$resource instanceof LocalResourceInterface) {
            throw new UnsupportedResourceException(sprintf(
                'LocalResourceCollection supports LocalResourceInterface '.
                'implementations only. Got: %s',
                get_class($resource)
            ));
        }

        parent::add($resource);
    }

    public function replace($resources)
    {
        if (!is_array($resources) && !$resources instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf(
                'The resources must be passed as array or traversable object. '.
                'Got: "%s"',
                is_object($resources) ? get_class($resources) : gettype($resources)
            ));
        }

        foreach ($resources as $resource) {
            if (!$resource instanceof LocalResourceInterface) {
                throw new UnsupportedResourceException(sprintf(
                    'LocalResourceCollection supports LocalResourceInterface '.
                    'implementations only. Got: %s',
                    get_class($resource)
                ));
            }
        }

        parent::replace($resources);
    }

    public function getLocalPaths()
    {
        return array_map(
            function (LocalResource $r) { return $r->getLocalPath(); },
            $this->toArray()
        );
    }
}
