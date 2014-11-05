<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Filesystem\Resource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceTest extends \PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/../../Fixtures');
    }

    /**
     * @expectedException \Webmozart\Puli\Filesystem\FilesystemException
     */
    public function testFailIfNonExistingFile()
    {
        new TestLocalResource($this->fixturesDir.'/foo/bar');
    }

    public function testGetLocalPath()
    {
        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
    }

    public function testGetAlternativePaths()
    {
        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAlternativePaths());
    }

    public function testGetAlternativePathsWithLoader()
    {
        $loader = $this->getMock('Webmozart\Puli\Filesystem\Resource\AlternativePathLoaderInterface');

        $loader->expects($this->once())
            ->method('loadAlternativePaths')
            ->with($this->isInstanceOf('Webmozart\Puli\Tests\Filesystem\Resource\TestLocalResource'))
            ->will($this->returnValue(array('/loaded/path')));

        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1', $loader);

        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAlternativePaths());
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\UnsupportedResourceException
     */
    public function testOverrideFailsIfNotLocalResource()
    {
        $directory = new TestLocalResource($this->fixturesDir.'/dir1/file1');

        $directory->override($this->getMock('Webmozart\Puli\Resource\ResourceInterface'));
    }

    public function testAddAlternativePathOnOverride()
    {
        $file = new TestLocalResource($this->fixturesDir.'/dir2');
        $overridden = new TestLocalResource($this->fixturesDir.'/dir1');

        $override = $file->override($overridden);

        $this->assertSame($this->fixturesDir.'/dir2', $override->getLocalPath());
        $this->assertSame(array(
            $this->fixturesDir.'/dir1',
            $this->fixturesDir.'/dir2',
        ), $override->getAlternativePaths());

        $file = new TestLocalResource($this->fixturesDir.'/file3');
        $override = $file->override($override);

        $this->assertSame($this->fixturesDir.'/file3', $override->getLocalPath());
        $this->assertSame(array(
            $this->fixturesDir.'/dir1',
            $this->fixturesDir.'/dir2',
            $this->fixturesDir.'/file3',
        ), $override->getAlternativePaths());
    }

    public function testOverrideWithOverride()
    {
        $file = new TestLocalResource($this->fixturesDir.'/file3');
        $override = $file->override(new TestLocalResource($this->fixturesDir.'/dir2'));

        $this->assertSame(array(
            $this->fixturesDir.'/dir2',
            $this->fixturesDir.'/file3',
        ), $override->getAlternativePaths());

        $file = new TestLocalResource($this->fixturesDir.'/dir1');
        $override = $override->override($file);

        $this->assertSame(array(
            $this->fixturesDir.'/dir1',
            $this->fixturesDir.'/dir2',
            $this->fixturesDir.'/file3',
        ), $override->getAlternativePaths());
    }
}
