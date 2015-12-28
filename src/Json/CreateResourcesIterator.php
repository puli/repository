<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Json;

use IteratorIterator;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use Puli\Repository\Resource\LinkResource;
use Traversable;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CreateResourcesIterator extends IteratorIterator
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    public function __construct(Traversable $iterator, ResourceRepository $repo)
    {
        parent::__construct($iterator);

        $this->repo = $repo;
    }

    public function current()
    {
        $path = parent::key();
        $reference = parent::current();

        if (isset($reference{0}) && '@' === $reference{0}) {
            $resource = new LinkResource(substr($reference, 1), $path);
        } elseif (is_dir($reference)) {
            $resource = new DirectoryResource($reference, $path);
        } elseif (null === $reference) {
            $resource = new GenericResource($path);
        } else {
            $resource = new FileResource($reference, $path);
        }

        $resource->attachTo($this->repo);

        return $resource;
    }
}
