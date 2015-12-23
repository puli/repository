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
use Puli\Repository\Api\UnsupportedOperationException;

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
    public function getStack($path)
    {
        if (!$this->changeStream) {
            throw new UnsupportedOperationException(sprintf(
                'Could not retrieve the resource stack for path "%s" as no ChangeStream was passed to the '.
                'constructor of the repository.',
                $path
            ));
        }

        return $this->changeStream->buildStack($this, $path);
    }

    /**
     * Append a change to the ChangeStream if possible.
     *
     * @param string       $path
     * @param PuliResource $resource
     */
    protected function appendToChangeStream($path, PuliResource $resource)
    {
        if ($this->changeStream) {
            $this->changeStream->append($resource);
        }
    }
}
