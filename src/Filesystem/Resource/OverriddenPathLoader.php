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

/**
 * A loader for the overridden paths of a file.
 *
 * A {@link LocalResource} instance can be associated to multiple files on the
 * file system. While {@link LocalResource::getLocalPath} returns the primary
 * local path only, {@link LocalResource::getAllLocalPaths} returns both the
 * primary path and all overridden paths.
 *
 * These paths can be loaded on demand by a path loader.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface OverriddenPathLoader
{
    /**
     * Loads the overridden path for a resource.
     *
     * @param LocalResource $resource The resource.
     *
     * @return string[] The overridden paths.
     */
    public function loadOverriddenPaths(LocalResource $resource);
}
