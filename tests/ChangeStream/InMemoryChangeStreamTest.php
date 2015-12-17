<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\ChangeStream;

use Puli\Repository\ChangeStream\InMemoryChangeStream;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class InMemoryChangeStreamTest extends AbstractChangeStreamTest
{
    protected function createChangeStream()
    {
        return new InMemoryChangeStream();
    }
}
