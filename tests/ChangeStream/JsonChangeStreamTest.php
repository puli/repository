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
use Puli\Repository\ChangeStream\JsonChangeStream;
use Webmozart\Glob\Test\TestUtil;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonChangeStreamTest extends AbstractChangeStreamTest
{
    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var string
     */
    private $path;

    protected function setUp()
    {
        $this->tempDir = TestUtil::makeTempDir('puli-repository', __CLASS__);
        $this->path = $this->tempDir.'/change-stream.json';

        parent::setUp();
    }

    protected function createWriteStream()
    {
        return new JsonChangeStream($this->path);
    }

    protected function createReadStream(ChangeStream $writeStream)
    {
        return new JsonChangeStream($this->path);
    }
}
