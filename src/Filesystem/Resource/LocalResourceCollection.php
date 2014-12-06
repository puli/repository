<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Resource;

use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Resource\ResourceInterface;

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
