<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\StreamWrapper;

use Webmozart\Puli\Locator\UriLocator;
use Webmozart\Puli\Repository\ResourceRepository;
use Webmozart\Puli\StreamWrapper\ResourceStreamWrapper;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapperTest extends \PHPUnit_Framework_TestCase
{
    const FILE1_CONTENT = <<<FILE1
LINE 1
LINE 2

FILE1;

    /**
     * @var ResourceRepository
     */
    private static $repository;

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
     * @var UriLocator
     */
    private $uriLocator;

    public static function setUpBeforeClass()
    {
        self::$repository = new ResourceRepository();
        self::$repository->add('/webmozart/puli', realpath(__DIR__.'/../Fixtures/dir1'));
        self::$repository->add('/webmozart/puli/dir2', realpath(__DIR__.'/../Fixtures/dir2'));
    }

    protected function setUp()
    {
        $this->uriLocator = new UriLocator();
        $this->uriLocator->register('puli', self::$repository);
        ResourceStreamWrapper::register($this->uriLocator);
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

        file_put_contents(__DIR__.'/../Fixtures/dir1/file1', self::FILE1_CONTENT);
    }

    public function testRead()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        $this->assertInternalType('resource', $this->handle);
        $this->assertSame('LI', fread($this->handle, 2));
        $this->assertSame("NE 1\n", fgets($this->handle));
        $this->assertSame("LINE 2\n", fgets($this->handle));
        $this->assertFalse(fgets($this->handle));
        $this->assertTrue(feof($this->handle));
        $this->assertTrue(fclose($this->handle));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testOpenInvalidUrl()
    {
        fopen('puli:///webmozart/puli/foobar', 'r');
    }

    public function testSeekSet()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        fread($this->handle, 2);

        $this->assertSame(0, fseek($this->handle, 1, SEEK_SET));
        $this->assertSame('INE 1', fread($this->handle, 5));
    }

    public function testSeekCur()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        fread($this->handle, 2);

        $this->assertSame(0, fseek($this->handle, 1, SEEK_CUR));
        $this->assertSame('E 1', fread($this->handle, 3));
    }

    public function testSeekInvalidPositiveOffset()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        // fseek() lets you seek non-existing positive offsets
        $this->assertSame(0, fseek($this->handle, 1000000));

        // the cursor is actually changed
        $this->assertSame(1000000, ftell($this->handle));
    }

    public function testSeekInvalidNegativeOffset()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        fseek($this->handle, 5);

        // large negative offsets are not allowed
        $this->assertSame(-1, fseek($this->handle, -1000000));

        // the cursor is unchanged
        $this->assertSame(5, ftell($this->handle));
    }

    public function testStat()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        $stat = fstat($this->handle);

        $this->assertInternalType('array', $stat);
        $this->assertArrayHasKey('dev', $stat);
        $this->assertArrayHasKey('ino', $stat);
        $this->assertArrayHasKey('mode', $stat);
    }

    public function testIsLocal()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

        $this->assertTrue(stream_is_local($this->handle));
    }

    public function testExists()
    {
        // uses url_stat()
        $this->assertTrue(file_exists('puli:///webmozart/puli/file1'));
        $this->assertFalse(file_exists('puli:///webmozart/puli/foobar'));
    }

    public function testLink()
    {
        $this->assertTrue(is_link('puli:///webmozart/puli/dir2/file1-link'));
        $this->assertFalse(is_link('puli:///webmozart/puli/dir2/file1'));
    }

    public function testSelect()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'r');

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

    public function testWrite()
    {
        $this->handle = fopen('puli:///webmozart/puli/file1', 'w+');

        $this->assertSame(8, fwrite($this->handle, 'NEW DATA', 10));
        $this->assertSame(4, fwrite($this->handle, 'NEW DATA', 4));
        $this->assertSame(0, fseek($this->handle, 0));
        $this->assertSame('NEW DATANEW ', fread($this->handle, 20));
    }

    public function testTruncate()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Not supported before PHP 5.4.0.');
        }

        $this->handle = fopen('puli:///webmozart/puli/file1', 'r+');

        $this->assertTrue(ftruncate($this->handle, 4));
        $this->assertSame(0, fseek($this->handle, 0));
        $this->assertSame('LINE', fread($this->handle, 20));
    }

    public function testTruncateInvalidSize()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Not supported before PHP 5.4.0.');
        }

        $this->handle = fopen('puli:///webmozart/puli/file1', 'r+');

        $this->assertFalse(ftruncate($this->handle, -4));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testUnlinkInvalidUrl()
    {
        unlink('puli:///webmozart/puli/foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testUnlinkIsProhibited()
    {
        unlink('puli:///webmozart/puli/file1');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testRenameInvalidUrl()
    {
        rename('puli:///webmozart/puli/foobar','puli:///webmozart/puli/baz');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testRenameIsProhibited()
    {
        rename('puli:///webmozart/puli/file1', 'puli:///webmozart/puli/baz');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testRmdirInvalidUrl()
    {
        rmdir('puli:///webmozart/foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testRmdirIsProhibited()
    {
        rmdir('puli:///webmozart/puli');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testMkdirIsProhibited()
    {
        mkdir('puli:///webmozart/puli/new-dir');
    }

    public function testListDirectory()
    {
        $this->dir = opendir('puli:///webmozart/puli');

        $this->assertInternalType('resource', $this->dir);
        $this->assertSame('dir2', readdir($this->dir));
        $this->assertSame('file1', readdir($this->dir));
        $this->assertSame('file2', readdir($this->dir));
        $this->assertFalse(readdir($this->dir));

        rewinddir($this->dir);

        $this->assertSame('dir2', readdir($this->dir));
    }

    public function testListMultipleDirectories()
    {
        // Test whether simultaneous traversal works
        $this->dir = opendir('puli:///webmozart/puli');
        $this->dir2 = opendir('puli:///webmozart/puli/dir2');

        $this->assertSame('file1', readdir($this->dir2));
        $this->assertSame('dir2', readdir($this->dir));
        $this->assertSame('file1-link', readdir($this->dir2));
        $this->assertSame('file1', readdir($this->dir));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testOpenNonExistingDirectory()
    {
        opendir('puli:///webmozart/puli/foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\NoDirectoryException
     */
    public function testOpenNonDirectory()
    {
        opendir('puli:///webmozart/puli/file1');
    }

    /**
     * @expectedException \Webmozart\Puli\StreamWrapper\StreamWrapperException
     */
    public function testRegisterTwice()
    {
        // first registration happens during setUp()
        ResourceStreamWrapper::register($this->uriLocator);
    }

    public function testUnregisterIsIdempotent()
    {
        ResourceStreamWrapper::unregister();
        ResourceStreamWrapper::unregister();
    }

    /**
     * @expectedException \Webmozart\Puli\StreamWrapper\StreamWrapperException
     */
    public function testWrapperShouldNotBeRegisteredManually()
    {
        ResourceStreamWrapper::unregister();

        stream_wrapper_register('manual', '\Webmozart\Puli\StreamWrapper\ResourceStreamWrapper');

        fopen('manual:///webmozart/puli/file1', 'r');
    }
}
