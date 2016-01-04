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

use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\ChangeStream\KeyValueStoreChangeStream;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class KeyValueStoreChangeStreamTest extends AbstractChangeStreamTest
{
    /**
     * @var ArrayStore
     */
    private $store;

    protected function setUp()
    {
        $this->store = new ArrayStore();

        parent::setUp();
    }

    protected function createWriteStream()
    {
        return new KeyValueStoreChangeStream($this->store);
    }

    /**
     * @param ChangeStream $writeStream
     *
     * @return ChangeStream
     */
    protected function createReadStream(ChangeStream $writeStream)
    {
        return new KeyValueStoreChangeStream($this->store);
    }
}
