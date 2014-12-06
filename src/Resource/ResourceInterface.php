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

/**
 * A resource.
 *
 * Resources are objects which can be stored in a resource repository. All
 * resources have a path, under which they are stored in the repository.
 * Depending on the implementation, resources may offer additional functionality.
 *
 * Resources that can be added to a {@link ManageableRepositoryInterface} should
 * implement {@link AttachableResourceInterface}.
 *
 * Resources that are similar to files in that they have a body and a size
 * should implement {@link FileResourceInterface}.
 *
 * Resources that contain other resources should implement
 * {@link DirectoryResourceInterface}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceInterface
{
    /**
     * Returns the path of the resource in the repository.
     *
     * @return string|null Returns the repository path of the resource.
     *                     If the resource is not attached to a repository,
     *                     `null` is returned.
     */
    public function getPath();

    /**
     * Returns the name of the resource.
     *
     * The name is the last segment of the resource's path.
     *
     * @return string|null Returns the name of the resource. If the resource is
     *                     not attached to a repository, `null` is returned.
     */
    public function getName();
}
