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

use PHPUnit_Framework_TestCase;
use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\InMemoryRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use Puli\Repository\Resource\LinkResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractChangeStreamTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return ChangeStream
     */
    abstract protected function createChangeStream();

    public function testBuildStack()
    {
        $stream = $this->createChangeStream();
        $stream->append($v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->append($v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->append($v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $repository = new InMemoryRepository();

        $stack = $stream->buildStack($repository, '/path');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $stack);
        $this->assertEquals(array(0, 1, 2), $stack->getVersions());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildStackFails()
    {
        $stream = $this->createChangeStream();
        $stream->append($v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->append($v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->append($v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $stream->buildStack(new InMemoryRepository(), '/invalid');
    }

    public function testGetVersion()
    {
        $stream = $this->createChangeStream();
        $stream->append($v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->append($v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->append($v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $repository = new InMemoryRepository();

        $stack = $stream->buildStack($repository, '/path');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $stack);

        $this->assertFileResourcesEquals($v1, $stack->get(0));
        $this->assertSame($repository, $stack->get(0)->getRepository());
        $this->assertFileResourcesEquals($v2, $stack->get(1));
        $this->assertSame($repository, $stack->get(1)->getRepository());
        $this->assertFileResourcesEquals($v3, $stack->get(2));
        $this->assertSame($repository, $stack->get(2)->getRepository());
    }

    public function testGetCurrentVersion()
    {
        $stream = $this->createChangeStream();
        $stream->append($v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->append($v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->append($v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $repository = new InMemoryRepository();

        $stack = $stream->buildStack($repository, '/path');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $stack);
        $this->assertSame(2, $stack->getCurrentVersion());
        $this->assertSame($v3, $stack->getCurrent());
        $this->assertSame($repository, $stack->getCurrent()->getRepository());
    }

    public function testGetFirstVersion()
    {
        $stream = $this->createChangeStream();
        $stream->append($v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->append($v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->append($v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $repository = new InMemoryRepository();

        $stack = $stream->buildStack($repository, '/path');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $stack);
        $this->assertSame(0, $stack->getFirstVersion());
        $this->assertSame($v1, $stack->getFirst());
        $this->assertSame($repository, $stack->getFirst()->getRepository());
    }

    public function testLogFile()
    {
        $stream = $this->createChangeStream();
        $stream->append(new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
    }

    public function testLogDirectory()
    {
        $stream = $this->createChangeStream();
        $stream->append(new DirectoryResource(__DIR__.'/../Fixtures/dir1', '/path'));
    }

    public function testLogLink()
    {
        $stream = $this->createChangeStream();
        $stream->append(new LinkResource(__DIR__.'/../Fixtures/dir1', '/path'));
    }

    public function testLogGeneric()
    {
        $stream = $this->createChangeStream();
        $stream->append(new GenericResource('/path'));
    }

    protected function assertFileResourcesEquals(FileResource $excepted, $actual)
    {
        $this->assertInstanceOf(get_class($excepted), $actual);
        $this->assertEquals($excepted->getPath(), $actual->getPath());
        $this->assertEquals($excepted->getFilesystemPath(), $actual->getFilesystemPath());
    }
}
