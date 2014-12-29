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

use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\LocalFileResource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResourceDirectoryTest extends AbstractDirectoryResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/Fixtures');

        parent::setUp();
    }

    /**
     * @param string|null $path
     *
     * @return LocalDirectoryResource
     */
    protected function createResource($path = null)
    {
        return new LocalDirectoryResource($this->fixturesDir.'/dir1', $path);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNoDirectory()
    {
        new LocalDirectoryResource($this->fixturesDir.'/dir1/file1');
    }

    public function testListEntriesDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $entries = $directory->listEntries();

        $this->assertCount(2, $entries);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\LocalResourceCollection', $entries);
        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file1'), $entries['file1']);
        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file2'), $entries['file2']);
    }

    public function testGetDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file1'), $directory->get('file1'));
    }

    public function testContainsDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $this->assertTrue($directory->contains('file1'));
        $this->assertTrue($directory->contains('file2'));
        $this->assertTrue($directory->contains('.'));
        $this->assertTrue($directory->contains('..'));
        $this->assertFalse($directory->contains('foobar'));
    }

    public function testCountDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir);

        $this->assertSame(3, $directory->count(false));
        $this->assertSame(8, $directory->count(true));
    }

}
