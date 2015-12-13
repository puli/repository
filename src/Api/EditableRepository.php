<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api;

use InvalidArgumentException;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\ChangeStream\ChangeStream;

/**
 * A repository that supports the addition and removal of resources.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface EditableRepository extends ResourceRepository
{
    /**
     * Adds a new resource to the repository.
     *
     * All resources passed to this method must implement {@link PuliResource}.
     *
     * @param string                          $path     The path at which to
     *                                                  add the resource.
     * @param PuliResource|ResourceCollection $resource The resource(s) to add
     *                                                  at that path.
     *
     * @throws InvalidArgumentException     If the path is invalid. The path
     *                                      must be  a non-empty string starting
     *                                      with "/".
     * @throws UnsupportedResourceException If the resource is invalid.
     */
    public function add($path, $resource);

    /**
     * Removes all resources matching the given query.
     *
     * @param string $query    A resource query.
     * @param string $language The language of the query. All implementations
     *                         must support the language "glob".
     *
     * @return int The number of resources removed from the repository.
     *
     * @throws InvalidArgumentException     If the query is invalid.
     * @throws UnsupportedLanguageException If the language is not supported.
     */
    public function remove($query, $language = 'glob');

    /**
     * Removes all resources from the repository.
     *
     * @return int The number of resources removed from the repository.
     */
    public function clear();

    /**
     * Set the change stream of this repository to log versions of resources as they change.
     *
     * @param ChangeStream|null $changeStream
     */
    public function setChangeStream(ChangeStream $changeStream = null);
}
