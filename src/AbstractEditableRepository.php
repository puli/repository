<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\PuliResource;

/**
 * Abstract base for editable repositories providing tools to avoid code duplication.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractEditableRepository extends AbstractRepository implements EditableRepository
{
    /**
     * @var ChangeStream
     */
    private $changeStream;

    /**
     * Create the repository.
     *
     * @param ChangeStream|null $changeStream If provided, the repository will log
     *                                        resources changes in this change stream.
     */
    public function __construct(ChangeStream $changeStream = null)
    {
        $this->changeStream = $changeStream;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions($path)
    {
        if (null === $this->changeStream) {
            return parent::getVersions($path);
        }

        return $this->changeStream->getVersions($path, $this);
    }

    /**
     * Stores a version of a resource in the change stream.
     *
     * @param PuliResource $resource The resource version.
     */
    protected function storeVersion(PuliResource $resource)
    {
        if (null !== $this->changeStream) {
            $this->changeStream->append($resource);
        }
    }

    /**
     * Removes all versions of a resource from the change stream.
     *
     * @param string $path The Puli path.
     */
    protected function removeVersions($path)
    {
        if (null !== $this->changeStream) {
            $this->changeStream->purge($path);
        }
    }

    /**
     * Clears the change stream.
     */
    protected function clearVersions()
    {
        if (null !== $this->changeStream) {
            $this->changeStream->clear();
        }
    }
}
