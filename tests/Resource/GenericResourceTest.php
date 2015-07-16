<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\Resource\GenericResource;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GenericResourceTest extends AbstractResourceTest
{
    protected function createResource($path = null)
    {
        return new GenericResource($path);
    }
}
