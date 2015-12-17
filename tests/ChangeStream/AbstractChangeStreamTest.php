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

    public function testBuildResourceStack()
    {
        $stream = $this->createChangeStream();
        $stream->append('/path', $v1 = new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
        $stream->append('/path', $v2 = new FileResource(__DIR__.'/../Fixtures/dir1/file2', '/path'));
        $stream->append('/path', $v3 = new FileResource(__DIR__.'/../Fixtures/dir2/file2', '/path'));

        $resourceStack = $stream->buildStack(new InMemoryRepository(), '/path');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $resourceStack);
        $this->assertCount(3, $resourceStack->getAvailableVersions());
        $this->assertFileResourcesEquals($v1, $resourceStack->getFirstVersion());
        $this->assertFileResourcesEquals($v1, $resourceStack->getVersion(0));
        $this->assertFileResourcesEquals($v2, $resourceStack->getVersion(1));
        $this->assertFileResourcesEquals($v3, $resourceStack->getVersion(2));
        $this->assertFileResourcesEquals($v3, $resourceStack->getCurrentVersion());
        $this->assertEquals(array(0, 1, 2), $resourceStack->getAvailableVersions());
    }

    public function testLogFile()
    {
        $stream = $this->createChangeStream();
        $stream->append('/path', new FileResource(__DIR__.'/../Fixtures/dir1/file1', '/path'));
    }

    public function testLogDirectory()
    {
        $stream = $this->createChangeStream();
        $stream->append('/path', new DirectoryResource(__DIR__.'/../Fixtures/dir1', '/path'));
    }

    public function testLogLink()
    {
        $stream = $this->createChangeStream();
        $stream->append('/path', new LinkResource(__DIR__.'/../Fixtures/dir1', '/path'));
    }

    public function testLogGeneric()
    {
        $stream = $this->createChangeStream();
        $stream->append('/path', new GenericResource('/path'));
    }

    protected function assertFileResourcesEquals(FileResource $excepted, $actual)
    {
        $this->assertInstanceOf(get_class($excepted), $actual);
        $this->assertEquals($excepted->getPath(), $actual->getPath());
        $this->assertEquals($excepted->getFilesystemPath(), $actual->getFilesystemPath());
    }
}
