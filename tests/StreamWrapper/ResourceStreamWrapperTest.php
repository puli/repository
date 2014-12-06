<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\StreamWrapper;

use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\StreamWrapper\ResourceStreamWrapper;
use Puli\Repository\Tests\Filesystem\TestLocalFile;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapperTest extends \PHPUnit_Framework_TestCase
{
    const FILE_CONTENTS = "LINE 1\nLINE 2\n";

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var resource
     */
    private $dir;

    /**
     * @var resource
     */
    private $dir2;

    /**
     * @var UriRepository
     */
    private $repo;

    protected function setUp()
    {
        $tempnam = tempnam(sys_get_temp_dir(), 'ResourceStreamWrapperTest');
        file_put_contents($tempnam, self::FILE_CONTENTS);

        $file = new TestLocalFile('/webmozart/puli/file', $tempnam);
        $dir = new TestDirectory('/webmozart/puli/dir');
        $nonLocal = new TestFile('/webmozart/puli/non-local');

        $this->repo = $this->getMock('Puli\Repository\Uri\UriRepositoryInterface');

        $this->repo->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($path) use ($tempnam, $dir, $nonLocal) {
                if ('puli:///webmozart/puli/file' === $path) {
                    return new TestLocalFile('/webmozart/puli/file', $tempnam);
                }
                if ('puli:///webmozart/puli/non-local' === $path) {
                    return new TestFile('/webmozart/puli/non-local');
                }
                if ('puli:///webmozart/puli/dir' === $path) {
                    return new TestDirectory('/webmozart/puli/dir', array(
                        new TestFile('/webmozart/puli/dir/file1'),
                        new TestFile('/webmozart/puli/dir/file2'),
                    ));
                }
                if ('puli:///webmozart/puli/dir2' === $path) {
                    return new TestDirectory('/webmozart/puli/dir2', array(
                        new TestFile('/webmozart/puli/dir2/.dotfile'),
                        new TestFile('/webmozart/puli/dir2/foo'),
                        new TestFile('/webmozart/puli/dir2/bar'),
                    ));
                }

                throw new ResourceNotFoundException();
            }));

        $this->repo->expects($this->any())
            ->method('getSupportedSchemes')
            ->will($this->returnValue(array('puli')));

        ResourceStreamWrapper::register($this->repo);
    }

    protected function tearDown()
    {
        ResourceStreamWrapper::unregister();

        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        if (is_resource($this->dir)) {
            closedir($this->dir);
        }

        if (is_resource($this->dir2)) {
            closedir($this->dir2);
        }

        if (in_array('manual', stream_get_wrappers())) {
            stream_wrapper_unregister('manual');
        }
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOpenNonFile()
    {
        fopen('puli:///webmozart/puli/dir', 'r');
    }

    public function provideFilePaths()
    {
        return array(
            array('puli:///webmozart/puli/file'),
            array('puli:///webmozart/puli/non-local'),
        );
    }

    /**
     * @dataProvider provideFilePaths
     */
    public function testRead($path)
    {
        $this->handle = fopen($path, 'r');

        $this->assertInternalType('resource', $this->handle);
        $this->assertSame('LI', fread($this->handle, 2));
        $this->assertSame("NE 1\n", fgets($this->handle));
        $this->assertSame("LINE 2\n", fgets($this->handle));
        $this->assertFalse(fgets($this->handle));
        $this->assertTrue(feof($this->handle));
        $this->assertTrue(fclose($this->handle));
    }

    /**
     * @dataProvider provideFilePaths
     */
    public function testSeekSet($path)
    {
        $this->handle = fopen($path, 'r');

        fread($this->handle, 2);

        $this->assertSame(0, fseek($this->handle, 1, SEEK_SET));
        $this->assertSame('INE 1', fread($this->handle, 5));
        $this->assertSame(6, ftell($this->handle));
    }

    /**
     * @dataProvider provideFilePaths
     */
    public function testSeekCur($path)
    {
        $this->handle = fopen($path, 'r');

        fread($this->handle, 2);

        $this->assertSame(0, fseek($this->handle, 1, SEEK_CUR));
        $this->assertSame('E 1', fread($this->handle, 3));
        $this->assertSame(6, ftell($this->handle));
    }

    public function testSeekInvalidPositiveOffsetLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/file', 'r');

        // fseek() lets you seek non-existing positive offsets
        $this->assertSame(0, fseek($this->handle, 1000000));

        // the cursor is actually changed
        $this->assertSame(1000000, ftell($this->handle));
    }

    public function testSeekInvalidPositiveOffsetNonLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/non-local', 'r');

        fseek($this->handle, 5);

        // php://temp does NOT let you seek non-existing positive offsets
        $this->assertSame(-1, fseek($this->handle, 1000000));

        // the cursor is not changed
        $this->assertSame(5, ftell($this->handle));
    }

    /**
     * @dataProvider provideFilePaths
     */
    public function testSeekInvalidNegativeOffset($path)
    {
        $this->handle = fopen($path, 'r');

        fseek($this->handle, 5);

        // large negative offsets are not allowed
        $this->assertSame(-1, fseek($this->handle, -1000000));

        // the cursor is not changed
        $this->assertSame(5, ftell($this->handle));
    }

    public function testStatLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/file', 'r');

        $stat = fstat($this->handle);

        $this->assertInternalType('array', $stat);
        $this->assertArrayHasKey('dev', $stat);
        $this->assertArrayHasKey('ino', $stat);
        $this->assertArrayHasKey('mode', $stat);
    }

    public function testStatNonLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/non-local', 'r');

        $stat = fstat($this->handle);

        $this->assertInternalType('array', $stat);
        $this->assertArrayHasKey('dev', $stat);
        $this->assertArrayHasKey('ino', $stat);
        $this->assertArrayHasKey('mode', $stat);
    }

    public function testIsLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/file', 'r');

        $this->assertTrue(stream_is_local($this->handle));
    }

    public function testIsNonLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/non-local', 'r');

        // stream_is_local() only returns false if STREAM_IS_URL is passed
        // when registering the "puli" protocol
        $this->assertTrue(stream_is_local($this->handle));
    }

    public function testExists()
    {
        // uses url_stat()
        $this->assertTrue(file_exists('puli:///webmozart/puli/file'));
        $this->assertTrue(file_exists('puli:///webmozart/puli/dir'));
        $this->assertTrue(file_exists('puli:///webmozart/puli/non-local'));
        $this->assertFalse(file_exists('puli:///webmozart/puli/foobar'));
    }

    /**
     * @dataProvider provideFilePaths
     */
    public function testSelect($path)
    {
        $this->handle = fopen($path, 'r');

        $read = array($this->handle);
        $write = array();
        $exceptional = array();
        $timeoutSec = 0;
        $timeoutMs = 0;

        $this->assertSame(
            1,
            stream_select($read, $write, $exceptional, $timeoutSec, $timeoutMs)
        );
    }

    /**
     * @dataProvider provideWriteModes
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testCannotOpenForWriting($mode)
    {
        fopen('puli:///webmozart/puli/file', $mode);
    }

    public function provideWriteModes()
    {
        return array(
            array('r+'),
            array('w'),
            array('w+'),
            array('a'),
            array('a+'),
            array('x'),
            array('x+'),
            array('c'),
            array('c+'),
        );
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testLockIsProhibited($path)
    {
        $this->handle = fopen($path, 'r');
        flock($this->handle, LOCK_SH);
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testTouchExistingIsProhibited($path)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Only supported in PHP 5.4+.');
            return;
        }

        touch($path);
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testTouchNewIsProhibited()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Only supported in PHP 5.4+.');
            return;
        }

        touch('puli:///webmozart/puli/new');
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testChownIsProhibited($path)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Only supported in PHP 5.4+.');
            return;
        }

        chown($path, 'root');
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testChgrpIsProhibited($path)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Only supported in PHP 5.4+.');
            return;
        }

        chgrp($path, 'root');
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testChmodIsProhibited($path)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Only supported in PHP 5.4+.');
            return;
        }

        chmod($path, 0777);
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testUnlinkIsProhibited($path)
    {
        unlink($path);
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testRenameIsProhibited()
    {
        rename('puli:///webmozart/puli/file', 'puli:///webmozart/puli/baz');
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testRmdirIsProhibited()
    {
        rmdir('puli:///webmozart/puli/dir');
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testMkdirIsProhibited()
    {
        mkdir('puli:///webmozart/puli/new-dir');
    }

    public function testListDirectory()
    {
        $this->dir = opendir('puli:///webmozart/puli/dir');

        $this->assertInternalType('resource', $this->dir);
        $this->assertSame('file1', readdir($this->dir));
        $this->assertSame('file2', readdir($this->dir));
        $this->assertFalse(readdir($this->dir));

        rewinddir($this->dir);

        $this->assertSame('file1', readdir($this->dir));
    }

    public function testListMultipleDirectories()
    {
        // Test whether simultaneous traversal works
        $this->dir = opendir('puli:///webmozart/puli/dir');
        $this->dir2 = opendir('puli:///webmozart/puli/dir2');

        $this->assertSame('file1', readdir($this->dir));
        $this->assertSame('.dotfile', readdir($this->dir2));
        $this->assertSame('file2', readdir($this->dir));
        $this->assertSame('foo', readdir($this->dir2));
        $this->assertFalse(readdir($this->dir));
        $this->assertSame('bar', readdir($this->dir2));
        $this->assertFalse(readdir($this->dir2));
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testOpenNonExistingDirectory()
    {
        opendir('puli:///webmozart/puli/foobar');
    }

    /**
     * @expectedException \Puli\Repository\NoDirectoryException
     */
    public function testOpenNonDirectory()
    {
        opendir('puli:///webmozart/puli/file');
    }

    /**
     * @expectedException \Puli\Repository\StreamWrapper\StreamWrapperException
     */
    public function testRegisterTwice()
    {
        // first registration happens during setUp()
        ResourceStreamWrapper::register($this->repo);
    }

    public function testUnregisterIsIdempotent()
    {
        ResourceStreamWrapper::unregister();
        ResourceStreamWrapper::unregister();
    }

    /**
     * @expectedException \Puli\Repository\StreamWrapper\StreamWrapperException
     */
    public function testWrapperShouldNotBeRegisteredManually()
    {
        ResourceStreamWrapper::unregister();

        stream_wrapper_register('manual', '\Puli\Repository\StreamWrapper\ResourceStreamWrapper');

        fopen('manual:///webmozart/puli/file1', 'r');
    }
}
