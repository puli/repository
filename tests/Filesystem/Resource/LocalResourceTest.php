<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Filesystem\Resource;

use Puli\Filesystem\Resource\OverriddenPathLoaderInterface;
use Puli\ResourceRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceTest extends \PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    /**
     * @expectedException \Puli\Filesystem\FilesystemException
     */
    public function testFailIfNonExistingFile()
    {
        new TestLocalResource($this->fixturesDir.'/foo/bar');
    }

    public function testCreateAttached()
    {
        $repo = $this->getMock('Puli\ResourceRepositoryInterface');

        $file = TestLocalResource::createAttached($repo, '/path', $this->fixturesDir.'/dir1/file1');

        $this->assertSame('/path', $file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testCreateAttachedWithPathLoader()
    {
        $repo = $this->getMock(__NAMESPACE__.'\TestPathLoader');

        $repo->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($this->isInstanceOf('Puli\Tests\Filesystem\Resource\TestLocalResource'))
            ->will($this->returnValue(array('/loaded/path')));

        $file = TestLocalResource::createAttached($repo, '/path', $this->fixturesDir.'/dir1/file1');

        $this->assertSame('/path', $file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());

        // Loader is called only once even for multiple calls
        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testCreateDetached()
    {
        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1');

        $this->assertNull($file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testAttach()
    {
        $repo = $this->getMock('Puli\ResourceRepositoryInterface');

        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1');
        $file->attachTo($repo, '/path');

        $this->assertSame('/path', $file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testAttachToPathLoader()
    {
        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1');
        $repo = $this->getMock(__NAMESPACE__.'\TestPathLoader');

        $repo->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($file)
            ->will($this->returnValue(array('/loaded/path')));

        $file->attachTo($repo, '/path');

        $this->assertSame('/path', $file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());

        // Loader is called only once even for multiple calls
        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testDetach()
    {
        $repo = $this->getMock(__NAMESPACE__.'\TestPathLoader');

        $repo->expects($this->never())
            ->method('loadOverriddenPaths');

        $file = TestLocalResource::createAttached($repo, '/path', $this->fixturesDir.'/dir1/file1');
        $file->detach();

        $this->assertNull($file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testDetachAfterLoadingOverriddenPaths()
    {
        $repo = $this->getMock(__NAMESPACE__.'\TestPathLoader');

        $repo->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($this->isInstanceOf('Puli\Tests\Filesystem\Resource\TestLocalResource'))
            ->will($this->returnValue(array('/loaded/path')));

        $file = TestLocalResource::createAttached($repo, '/path', $this->fixturesDir.'/dir1/file1');

        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());

        $file->detach();

        $this->assertNull($file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());
        $this->assertSame(array('/loaded/path', $this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    public function testAttachAfterLoadingOverriddenPaths()
    {
        $repo = $this->getMock(__NAMESPACE__.'\TestPathLoader');

        $repo->expects($this->never())
            ->method('loadOverriddenPaths');

        $file = new TestLocalResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());

        $file->attachTo($repo, '/path');

        $this->assertSame('/path', $file->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $file->getLocalPath());

        // The loader is not called anymore as paths have been loaded already
        $this->assertSame(array($this->fixturesDir.'/dir1/file1'), $file->getAllLocalPaths());
    }

    /**
     * @expectedException \Puli\UnsupportedResourceException
     */
    public function testOverrideFailsIfNotLocalResource()
    {
        $directory = new TestLocalResource($this->fixturesDir.'/dir1/file1');

        $directory->override($this->getMock('Puli\Resource\ResourceInterface'));
    }

    public function testOverrideDetached()
    {
        $file = new TestLocalResource($this->fixturesDir.'/dir2');
        $overridden = new TestLocalResource($this->fixturesDir.'/dir1');

        $file->override($overridden);

        $this->assertSame($this->fixturesDir.'/dir2', $file->getLocalPath());
        $this->assertSame(array(
            $this->fixturesDir.'/dir1',
            $this->fixturesDir.'/dir2',
        ), $file->getAllLocalPaths());

        $file2 = new TestLocalResource($this->fixturesDir.'/file3');
        $file2->override($file);

        $this->assertSame($this->fixturesDir.'/file3', $file2->getLocalPath());
        $this->assertSame(array(
            $this->fixturesDir.'/dir1',
            $this->fixturesDir.'/dir2',
            $this->fixturesDir.'/file3',
        ), $file2->getAllLocalPaths());
    }

    public function testOverrideTwiceDetached()
    {
        $file = new TestLocalResource($this->fixturesDir.'/file3');
        $overridden1 = new TestLocalResource($this->fixturesDir.'/dir1');
        $overridden2 = new TestLocalResource($this->fixturesDir.'/dir2');

        $file->override($overridden1);
        $file->override($overridden2);

        $this->assertSame($this->fixturesDir.'/file3', $file->getLocalPath());
        $this->assertSame(array(
            $this->fixturesDir.'/dir2',
            $this->fixturesDir.'/dir1',
            $this->fixturesDir.'/file3',
        ), $file->getAllLocalPaths());
    }
}

interface TestPathLoader extends ResourceRepositoryInterface, OverriddenPathLoaderInterface {}
