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
use Puli\Repository\Resource\FileResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractChangeStreamTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var InMemoryRepository
     */
    protected $repo;

    /**
     * @var ChangeStream
     */
    protected $stream;

    /**
     * @var string
     */
    protected $fixtureDir;

    protected function setUp()
    {
        $this->repo = new InMemoryRepository();
        $this->stream = $this->createChangeStream();
        $this->fixtureDir = __DIR__.'/../Fixtures';
    }

    /**
     * @return ChangeStream
     */
    abstract protected function createChangeStream();

    public function testAppend()
    {
        $this->stream->append($v1 = new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->stream->append($v2 = new FileResource($this->fixtureDir.'/dir1/file2', '/path'));
        $this->stream->append($v3 = new FileResource($this->fixtureDir.'/dir2/file2', '/path'));

        $versions = $this->stream->getVersions('/path');

        $this->assertInstanceOf('Puli\Repository\Api\ChangeStream\VersionList', $versions);
        $this->assertSame('/path', $versions->getPath());
        $this->assertEquals(array(0, 1, 2), $versions->getVersions());
        $this->assertSame($v1, $versions->get(0));
        $this->assertSame($v2, $versions->get(1));
        $this->assertSame($v3, $versions->get(2));
    }

    public function testContains()
    {
        $this->assertFalse($this->stream->contains('/path'));

        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));

        $this->assertTrue($this->stream->contains('/path'));
    }

    public function testPurge()
    {
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path1'));
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path1'));
        $this->stream->append(new FileResource($this->fixtureDir.'/dir2/file2', '/path2'));

        $this->stream->purge('/path1');

        $this->assertFalse($this->stream->contains('/path1'));
        $this->assertTrue($this->stream->contains('/path2'));
    }

    public function testClear()
    {
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path1'));
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path1'));
        $this->stream->append(new FileResource($this->fixtureDir.'/dir2/file2', '/path2'));

        $this->stream->clear();

        $this->assertFalse($this->stream->contains('/path1'));
        $this->assertFalse($this->stream->contains('/path2'));
    }

    public function testAppendAfterPurging()
    {
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));

        $this->stream->purge('/path');

        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $this->assertTrue($this->stream->contains('/path'));
        $this->assertCount(1, $this->stream->getVersions('/path'));
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsIfNotFound()
    {
        $this->stream->getVersions('/foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsAfterPurging()
    {
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $this->stream->purge('/path');

        $this->stream->getVersions('/path');
    }

    public function testResourcesAttachedToRepositoryIfPassed()
    {
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->stream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $versions = $this->stream->getVersions('/path', $this->repo);

        $this->assertCount(2, $versions);
        $this->assertSame($this->repo, $versions->get(0)->getRepository());
        $this->assertSame($this->repo, $versions->get(1)->getRepository());
    }
}
