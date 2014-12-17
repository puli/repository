<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\ResourceNotFoundException;

/**
 * A resource which acts as directory in the repository.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface DirectoryResource extends Resource
{
    /**
     * Returns the resource with the given name from the directory.
     *
     * "." and ".." are supported as names.
     *
     * @param string $name The name of the resource.
     *
     * @return Resource The resource with the given name.
     *
     * @throws ResourceNotFoundException If the resource cannot be found.
     */
    public function get($name);

    /**
     * Returns whether the resource with the given name exists in the directory.
     *
     * @param string $name The name of the resource.
     *
     * @return boolean Whether a resource with the given name exists.
     */
    public function contains($name);

    /**
     * Lists all resources in the directory.
     *
     * @return ResourceCollection The resources indexed by their names.
     */
    public function listEntries();
}
