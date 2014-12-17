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

use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\Resource;

/**
 * A collection of local resources.
 *
 * The resource collection contains the additional method {@link getLocalPaths}
 * for batch collecting the local paths of all contained resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceCollection extends ArrayResourceCollection
{
    /**
     * Returns the local paths of all contained resources.
     *
     * The paths are contained in order of the resources. Non-local resources
     * are ignored and not represented in the output.
     *
     * @return string[] The local paths.
     */
    public function getLocalPaths()
    {
        return array_map(
            function (LocalResource $r) {
                return $r->getLocalPath();
            },
            array_filter(
                $this->toArray(),
                function (Resource $r) {
                    return $r instanceof LocalResource;
                }
            )
        );
    }
}
