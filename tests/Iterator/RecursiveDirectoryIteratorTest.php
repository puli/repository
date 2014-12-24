<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Iterator;

use PHPUnit_Framework_TestCase;
use Puli\Repository\Iterator\RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RecursiveDirectoryIteratorTest extends PHPUnit_Framework_TestCase
{
    private $tempDir;

    protected function setUp()
    {
        while (false === mkdir($this->tempDir = sys_get_temp_dir().'/puli-repository/RecursiveDirectoryIteratorTest'.rand(10000, 99999), 0777, true)) {}

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/Fixtures', $this->tempDir);
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    public function testIterate()
    {
        $iterator = new RecursiveDirectoryIterator($this->tempDir);

        $this->assertSame(array(
            $this->tempDir.'/base.css' => $this->tempDir.'/base.css',
            $this->tempDir.'/css' => $this->tempDir.'/css',
            $this->tempDir.'/js' => $this->tempDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testIterateTrailingSlash()
    {
        $iterator = new RecursiveDirectoryIterator($this->tempDir.'/');

        $this->assertSame(array(
            $this->tempDir.'/base.css' => $this->tempDir.'/base.css',
            $this->tempDir.'/css' => $this->tempDir.'/css',
            $this->tempDir.'/js' => $this->tempDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testIterateCurrentAsPath()
    {
        $iterator = new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::CURRENT_AS_PATH);

        $this->assertSame(array(
            $this->tempDir.'/base.css' => $this->tempDir.'/base.css',
            $this->tempDir.'/css' => $this->tempDir.'/css',
            $this->tempDir.'/js' => $this->tempDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testIterateCurrentAsFile()
    {
        $iterator = new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::CURRENT_AS_FILE);

        $this->assertSame(array(
            $this->tempDir.'/base.css' => 'base.css',
            $this->tempDir.'/css' => 'css',
            $this->tempDir.'/js' => 'js',
        ), iterator_to_array($iterator));
    }

    public function testIterateRecursively()
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::CURRENT_AS_FILE),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $this->assertSame(array(
            $this->tempDir.'/base.css' => 'base.css',
            $this->tempDir.'/css' => 'css',
            $this->tempDir.'/css/reset.css' => 'reset.css',
            $this->tempDir.'/css/style.css' => 'style.css',
            $this->tempDir.'/js' => 'js',
            $this->tempDir.'/js/script.js' => 'script.js',
        ), iterator_to_array($iterator));
    }

    public function testSeek()
    {
        $iterator = new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::CURRENT_AS_FILE);

        $iterator->seek(0);
        $this->assertSame($this->tempDir.'/base.css', $iterator->key());
        $this->assertSame('base.css', $iterator->current());

        $iterator->seek(1);
        $this->assertSame($this->tempDir.'/css', $iterator->key());
        $this->assertSame('css', $iterator->current());

        $iterator->seek(2);
        $this->assertSame($this->tempDir.'/js', $iterator->key());
        $this->assertSame('js', $iterator->current());

        $iterator->seek(0);
        $this->assertSame($this->tempDir.'/base.css', $iterator->key());
        $this->assertSame('base.css', $iterator->current());
    }

    public function testIterateWithConcurrentDeletions()
    {
        $iterator = new RecursiveDirectoryIterator($this->tempDir);
        $iterator->rewind();

        $this->assertTrue($iterator->valid());
        $this->assertSame($this->tempDir.'/base.css', $iterator->key());
        $this->assertSame($this->tempDir.'/base.css', $iterator->current());

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir.'/css');

        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame($this->tempDir.'/js', $iterator->key());
        $this->assertSame($this->tempDir.'/js', $iterator->current());

        $iterator->next();

        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNonExistingBaseDirectory()
    {
        new RecursiveDirectoryIterator($this->tempDir.'/foobar');
    }
}
