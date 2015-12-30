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
    public function getStack($path)
    {
        if (!$this->changeStream) {
            return parent::getStack($path);
        }

        return $this->changeStream->buildStack($this, $path);
    }

    /**
     * Append a change to the ChangeStream if possible.
     *
     * @param PuliResource $resource
     */
    protected function appendToChangeStream(PuliResource $resource)
    {
        if ($this->changeStream) {
            $this->changeStream->append($resource);
        }
    }
}
