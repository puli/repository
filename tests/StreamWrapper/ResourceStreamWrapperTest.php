<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\StreamWrapper;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_TestCase;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\StreamWrapper\ResourceStreamWrapper;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapperTest extends PHPUnit_Framework_TestCase
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
     * @var \Puli\Repository\Api\ResourceRepository
     */
    private $repo;

    protected function setUp()
    {
        $tempnam = tempnam(sys_get_temp_dir(), 'ResourceStreamWrapperTest');
        file_put_contents($tempnam, self::FILE_CONTENTS);

        $this->repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $this->repo->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($path) use ($tempnam) {
                if ('/webmozart/puli/file' === $path) {
                    return new FileResource($tempnam, '/webmozart/puli/file');
                }
                if ('/webmozart/puli/non-local' === $path) {
                    return new TestFile('/webmozart/puli/non-local');
                }
                if ('/webmozart/puli/dir' === $path) {
                    return new TestDirectory('/webmozart/puli/dir', array(
                        new TestFile('/webmozart/puli/dir/file1'),
                        new TestFile('/webmozart/puli/dir/file2'),
                    ));
                }
                if ('/webmozart/puli/dir2' === $path) {
                    return new TestDirectory('/webmozart/puli/dir2', array(
                        new TestFile('/webmozart/puli/dir2/.dotfile'),
                        new TestFile('/webmozart/puli/dir2/foo'),
                        new TestFile('/webmozart/puli/dir2/bar'),
                    ));
                }

                throw new ResourceNotFoundException();
            }));
    }

    protected function tearDown()
    {
        ResourceStreamWrapper::unregister('puli');

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
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testOpenNonFile()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen($path, 'r');

        fread($this->handle, 2);

        $this->assertSame(0, fseek($this->handle, 1, SEEK_CUR));
        $this->assertSame('E 1', fread($this->handle, 3));
        $this->assertSame(6, ftell($this->handle));
    }

    public function testSeekInvalidPositiveOffsetLocal()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen('puli:///webmozart/puli/file', 'r');

        // fseek() lets you seek non-existing positive offsets
        $this->assertSame(0, fseek($this->handle, 1000000));

        // the cursor is actually changed
        $this->assertSame(1000000, ftell($this->handle));
    }

    public function testSeekInvalidPositiveOffsetNonLocal()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen($path, 'r');

        fseek($this->handle, 5);

        // large negative offsets are not allowed
        $this->assertSame(-1, fseek($this->handle, -1000000));

        // the cursor is not changed
        $this->assertSame(5, ftell($this->handle));
    }

    public function testStatLocal()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen('puli:///webmozart/puli/file', 'r');

        $stat = fstat($this->handle);

        $this->assertInternalType('array', $stat);
        $this->assertArrayHasKey('dev', $stat);
        $this->assertArrayHasKey('ino', $stat);
        $this->assertArrayHasKey('mode', $stat);
    }

    public function testStatNonLocal()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen('puli:///webmozart/puli/non-local', 'r');

        $stat = fstat($this->handle);

        $this->assertInternalType('array', $stat);
        $this->assertArrayHasKey('dev', $stat);
        $this->assertArrayHasKey('ino', $stat);
        $this->assertArrayHasKey('mode', $stat);
    }

    public function testIsLocal()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen('puli:///webmozart/puli/file', 'r');

        $this->assertTrue(stream_is_local($this->handle));
    }

    public function testIsNonLocal()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        $this->handle = fopen('puli:///webmozart/puli/non-local', 'r');

        // stream_is_local() only returns false if STREAM_IS_URL is passed
        // when registering the "puli" protocol
        $this->assertTrue(stream_is_local($this->handle));
    }

    public function testExists()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

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

        ResourceStreamWrapper::register('puli', $this->repo);

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

        ResourceStreamWrapper::register('puli', $this->repo);

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

        ResourceStreamWrapper::register('puli', $this->repo);

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

        ResourceStreamWrapper::register('puli', $this->repo);

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

        ResourceStreamWrapper::register('puli', $this->repo);

        chmod($path, 0777);
    }

    /**
     * @dataProvider provideFilePaths
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testUnlinkIsProhibited($path)
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        unlink($path);
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testRenameIsProhibited()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        rename('puli:///webmozart/puli/file', 'puli:///webmozart/puli/baz');
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testRmdirIsProhibited()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        rmdir('puli:///webmozart/puli/dir');
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedOperationException
     */
    public function testMkdirIsProhibited()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        mkdir('puli:///webmozart/puli/new-dir');
    }

    public function testListDirectory()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

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
        ResourceStreamWrapper::register('puli', $this->repo);

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
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testOpenNonExistingDirectory()
    {
        ResourceStreamWrapper::register('puli', $this->repo);

        opendir('puli:///webmozart/puli/foobar');
    }

    public function testRegisterCallable()
    {
        $repo = $this->repo;

        ResourceStreamWrapper::register('puli', function () use ($repo) {
            return $repo;
        });

        $this->assertSame(self::FILE_CONTENTS, file_get_contents('puli:///webmozart/puli/file'));
    }

    public function testCallableNotInvokedIfNotUsed()
    {
        $repo = $this->repo;

        ResourceStreamWrapper::register('puli', function () use ($repo) {
            return $repo;
        });

        ResourceStreamWrapper::register('unused', function () {
            PHPUnit_Framework_Assert::fail('Should not be called.');
        });

        $this->assertSame(self::FILE_CONTENTS, file_get_contents('puli:///webmozart/puli/file'));
    }

    /**
     * @expectedException \Puli\Repository\RepositoryFactoryException
     * @expectedExceptionMessage stdClass
     */
    public function testFailIfCallableDoesNotReturnValidRepository()
    {
        ResourceStreamWrapper::register('puli', function () {
            return new \stdClass();
        });

        file_get_contents('puli:///webmozart/puli/file');
    }

    /**
     * @expectedException \Puli\Repository\StreamWrapper\StreamWrapperException
     */
    public function testRegisterTwice()
    {
        ResourceStreamWrapper::register('puli', $this->repo);
        ResourceStreamWrapper::register('puli', $this->repo);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterFailsIfNotRepoNorCallable()
    {
        ResourceStreamWrapper::register('puli', 'foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Got: integer
     */
    public function testRegisterFailsIfSchemeNotString()
    {
        ResourceStreamWrapper::register(1234, $this->repo);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage letters and digits
     */
    public function testRegisterFailsIfSchemeContainsSpecialChars()
    {
        ResourceStreamWrapper::register('puli{', $this->repo);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage start with a letter
     */
    public function testRegisterFailsIfSchemeDoesNotStartWithLetter()
    {
        ResourceStreamWrapper::register('1puli', $this->repo);
    }

    public function testUnregisterIsIdempotent()
    {
        ResourceStreamWrapper::unregister('puli');
        ResourceStreamWrapper::unregister('puli');
    }

    /**
     * @expectedException \Puli\Repository\StreamWrapper\StreamWrapperException
     */
    public function testWrapperShouldNotBeRegisteredManually()
    {
        stream_wrapper_register('manual', '\Puli\Repository\StreamWrapper\ResourceStreamWrapper');

        fopen('manual:///webmozart/puli/file1', 'r');
    }
}
