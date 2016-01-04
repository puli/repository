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
    protected $writeStream;

    /**
     * @var ChangeStream
     */
    protected $readStream;

    /**
     * @var string
     */
    protected $fixtureDir;

    protected function setUp()
    {
        $this->repo = new InMemoryRepository();
        $this->writeStream = $this->createWriteStream();
        $this->readStream = $this->createReadStream($this->writeStream);
        $this->fixtureDir = __DIR__.'/../Fixtures';
    }

    /**
     * @return ChangeStream
     */
    abstract protected function createWriteStream();

    /**
     * @param ChangeStream $writeStream
     *
     * @return ChangeStream
     */
    abstract protected function createReadStream(ChangeStream $writeStream);

    public function testAppend()
    {
        $this->writeStream->append($v1 = new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->writeStream->append($v2 = new FileResource($this->fixtureDir.'/dir1/file2', '/path'));
        $this->writeStream->append($v3 = new FileResource($this->fixtureDir.'/dir2/file2', '/path'));

        $versions = $this->readStream->getVersions('/path');

        $this->assertInstanceOf('Puli\Repository\Api\ChangeStream\VersionList', $versions);
        $this->assertSame('/path', $versions->getPath());
        $this->assertEquals(array(0, 1, 2), $versions->getVersions());
        $this->assertSame($v1, $versions->get(0));
        $this->assertSame($v2, $versions->get(1));
        $this->assertSame($v3, $versions->get(2));
    }

    public function testContains()
    {
        $this->assertFalse($this->readStream->contains('/path'));

        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));

        $this->readStream = $this->createReadStream($this->writeStream);

        $this->assertTrue($this->readStream->contains('/path'));
    }

    public function testPurge()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path1'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path1'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir2/file2', '/path2'));

        $this->writeStream->purge('/path1');

        $this->assertFalse($this->readStream->contains('/path1'));
        $this->assertTrue($this->readStream->contains('/path2'));
    }

    public function testClear()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path1'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path1'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir2/file2', '/path2'));

        $this->writeStream->clear();

        $this->assertFalse($this->readStream->contains('/path1'));
        $this->assertFalse($this->readStream->contains('/path2'));
    }

    public function testAppendAfterPurging()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));

        $this->writeStream->purge('/path');

        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $this->assertTrue($this->readStream->contains('/path'));
        $this->assertCount(1, $this->readStream->getVersions('/path'));
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsIfNotFound()
    {
        $this->readStream->getVersions('/foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsAfterPurging()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $this->writeStream->purge('/path');

        $this->readStream->getVersions('/path');
    }

    public function testResourcesNotAttachedToRepositoryByDefault()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $versions = $this->readStream->getVersions('/path');

        $this->assertCount(2, $versions);
        $this->assertNull($versions->get(0)->getRepository());
        $this->assertNull($versions->get(1)->getRepository());
    }

    public function testResourcesAttachedToRepositoryIfPassed()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        $versions = $this->readStream->getVersions('/path', $this->repo);

        $this->assertCount(2, $versions);
        $this->assertSame($this->repo, $versions->get(0)->getRepository());
        $this->assertSame($this->repo, $versions->get(1)->getRepository());
    }

    public function testResourcesInStreamRemainDetached()
    {
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file1', '/path'));
        $this->writeStream->append(new FileResource($this->fixtureDir.'/dir1/file2', '/path'));

        // attached versions
        $this->readStream->getVersions('/path', $this->repo);

        // still detached (clones)
        $versions = $this->readStream->getVersions('/path');

        $this->assertCount(2, $versions);
        $this->assertNull($versions->get(0)->getRepository());
        $this->assertNull($versions->get(1)->getRepository());
    }
}
