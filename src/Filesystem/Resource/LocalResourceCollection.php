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
 * A collection of local resources.
 *
 * The resource collection contains the additional method {@link getLocalPaths}
 * for batch collecting the local paths of all contained resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceCollection extends ResourceCollection
{
    /**
     * Returns the local paths of all contained resources.
     *
     * The paths are contained in order of the resources. If a resource is not
     * local, `null` is returned as path.
     *
     * @return string[] The local paths.
     */
    public function getLocalPaths()
    {
        return array_map(
            function (ResourceInterface $r) {
                return $r instanceof LocalResourceInterface
                    ? $r->getLocalPath()
                    : null;
            },
            $this->toArray()
        );
    }
}
