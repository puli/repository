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
use Puli\Repository\Resource\OverriddenPathLoader;
use Puli\Repository\Tests\Resource\AbstractFileResourceTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalFileResourceTest extends AbstractLocalResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        parent::setUp();

        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    protected function createLocalResource($localPath, $path = null, OverriddenPathLoader $pathLoader = null)
    {
        return new LocalFileResource($localPath, $path, $pathLoader);
    }

    protected function getValidLocalPath()
    {
        return $this->fixturesDir.'/dir1/file1';
    }

    protected function getValidLocalPath2()
    {
        return $this->fixturesDir.'/dir1/file2';
    }

    protected function getValidLocalPath3()
    {
        return $this->fixturesDir.'/dir2/file1';
    }

    public function getInvalidLocalPaths()
    {
        // setUp() has not yet been called in the data provider
        $fixturesDir = realpath(__DIR__.'/Fixtures');

        return array(
            // Not a file
            array($fixturesDir.'/dir1'),
            // Does not exist
            array($fixturesDir.'/foobar'),
        );
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalResource()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $file->override(new TestLocalResource($this->fixturesDir.'/dir1/file1'));
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalDirectoryResource()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $file->override(new LocalDirectoryResource($this->fixturesDir.'/dir1'));
    }

    public function testGetContents()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(file_get_contents($file->getLocalPath()), $file->getContents());
    }

    public function testGetSize()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(filesize($this->fixturesDir.'/dir1/file1'), $file->getSize());
        $this->assertSame(strlen($file->getContents()), $file->getSize());
    }

    public function testGetLastAccessedAt()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(fileatime($this->fixturesDir.'/dir1/file1'), $file->getLastAccessedAt());
    }

    public function testGetLastModifiedAt()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(filemtime($this->fixturesDir.'/dir1/file1'), $file->getLastModifiedAt());
    }
}
