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

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\UnsupportedOperationException;
use Puli\Repository\ChangeStream\ChangeStream;

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
    protected $changeStream;

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
    public function getStack($path)
    {
        if (!$this->changeStream) {
            throw new UnsupportedOperationException(sprintf(
                'Impossible to retrieve resource stack for path "%s" as no ChangeStream is configured.',
                $path
            ));
        }

        return $this->changeStream->buildResourceStack($this, $path);
    }

    /**
     * @param ChangeStream $changeStream
     */
    public function setChangeStream(ChangeStream $changeStream = null)
    {
        $this->changeStream = $changeStream;
    }

    /**
     * Log a change in the ChangeStream if possible.
     *
     * @param string       $path
     * @param PuliResource $resource
     */
    public function logChange($path, PuliResource $resource)
    {
        if ($this->changeStream) {
            $this->changeStream->log($path, $resource);
        }
    }
}
