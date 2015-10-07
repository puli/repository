<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Collection;

use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\PuliResource;

/**
 * A collection of resources on the filesystem.
 *
 * The resource collection contains the additional method
 * {@link getFilesystemPaths()} for batch collecting the filesystem paths of all
 * contained resources.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemResourceCollection extends ArrayResourceCollection
{
    /**
     * Returns the filesystem paths of all contained resources.
     *
     * The paths are returned in order of the resources. Resources that are not
     * on the filesystem are ignored and not contained in the output.
     *
     * @return string[] The filesystem paths.
     */
    public function getFilesystemPaths()
    {
        return array_map(
            function (FilesystemResource $resource) {
                return $resource->getFilesystemPath();
            },
            array_filter(
                $this->toArray(),
                function (PuliResource $r) {
                    return $r instanceof FilesystemResource && null !== $r->getFilesystemPath();
                }
            )
        );
    }
}
