<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Resource;

/**
 * A loader for the overridden paths of a file.
 *
 * A {@link LocalResourceInterface} instance can be associated to multiple files
 * on the file system. While {@link LocalResourceInterface::getLocalPath}
 * returns the primary local path only, {@link LocalResourceInterface::getAllLocalPaths}
 * returns both the primary path and all overridden paths.
 *
 * These paths can be loaded on demand by a path loader.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface OverriddenPathLoaderInterface
{
    /**
     * Loads the overridden path for a resource.
     *
     * @param LocalResourceInterface $resource The resource.
     *
     * @return string[] The overridden paths.
     */
    public function loadOverriddenPaths(LocalResourceInterface $resource);
}
