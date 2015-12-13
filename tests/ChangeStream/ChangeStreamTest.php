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

use Puli\Repository\ChangeStream\ChangeStream;
use Puli\Repository\InMemoryRepository;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestNullResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ChangeStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testLogStack()
    {
        $stream = new ChangeStream();
        $stream->log('/path', $v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->log('/path', $v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->log('/path', $v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $stack = $stream->getLogStack();

        $this->assertCount(1, $stack);
        $this->assertArrayHasKey('/path', $stack);
        $this->assertCount(3, $stack['/path']);
        $this->assertSame(__DIR__.'/../Fixtures/dir1/file1', $stack['/path'][0]['filesystem_path']);
        $this->assertSame(__DIR__.'/../Fixtures/dir1/file2', $stack['/path'][1]['filesystem_path']);
        $this->assertSame(__DIR__.'/../Fixtures/dir2/file2', $stack['/path'][2]['filesystem_path']);
    }

    public function testBuildResourceStack()
    {
        $stream = new ChangeStream();
        $stream->log('/path', $v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->log('/path', $v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->log('/path', $v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $resourceStack = $stream->buildResourceStack(new InMemoryRepository(), '/path');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $resourceStack);
        $this->assertCount(3, $resourceStack);
        $this->assertFileResourcesEquals($v1, $resourceStack->getFirstVersion());
        $this->assertFileResourcesEquals($v1, $resourceStack->getVersion(0));
        $this->assertFileResourcesEquals($v2, $resourceStack->getVersion(1));
        $this->assertFileResourcesEquals($v3, $resourceStack->getVersion(2));
        $this->assertFileResourcesEquals($v3, $resourceStack->getCurrentVersion());
        $this->assertEquals(array(0, 1, 2), $resourceStack->getAvailableVersions());
    }

    private function assertFileResourcesEquals(FileResource $excepted, $actual)
    {
        $this->assertInstanceOf(get_class($excepted), $actual);
        $this->assertEquals($excepted->getPath(), $actual->getPath());
        $this->assertEquals($excepted->getFilesystemPath(), $actual->getFilesystemPath());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUnsupportedResource()
    {
        $stream = new ChangeStream();
        $stream->log('/path', new TestNullResource());
    }
}
