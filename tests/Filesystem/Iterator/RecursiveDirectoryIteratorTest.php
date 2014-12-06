<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem\Iterator;

use Puli\Repository\Filesystem\Iterator\RecursiveDirectoryIterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RecursiveDirectoryIteratorTest extends \PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = __DIR__.'/Fixtures';
    }

    public function testIterate()
    {
        $iterator = new RecursiveDirectoryIterator($this->fixturesDir);

        $this->assertSame(array(
            $this->fixturesDir.'/base.css' => $this->fixturesDir.'/base.css',
            $this->fixturesDir.'/css' => $this->fixturesDir.'/css',
            $this->fixturesDir.'/js' => $this->fixturesDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testIterateTrailingSlash()
    {
        $iterator = new RecursiveDirectoryIterator($this->fixturesDir.'/');

        $this->assertSame(array(
            $this->fixturesDir.'/base.css' => $this->fixturesDir.'/base.css',
            $this->fixturesDir.'/css' => $this->fixturesDir.'/css',
            $this->fixturesDir.'/js' => $this->fixturesDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testIterateCurrentAsPath()
    {
        $iterator = new RecursiveDirectoryIterator($this->fixturesDir, RecursiveDirectoryIterator::CURRENT_AS_PATH);

        $this->assertSame(array(
            $this->fixturesDir.'/base.css' => $this->fixturesDir.'/base.css',
            $this->fixturesDir.'/css' => $this->fixturesDir.'/css',
            $this->fixturesDir.'/js' => $this->fixturesDir.'/js',
        ), iterator_to_array($iterator));
    }

    public function testIterateCurrentAsFile()
    {
        $iterator = new RecursiveDirectoryIterator($this->fixturesDir, RecursiveDirectoryIterator::CURRENT_AS_FILE);

        $this->assertSame(array(
            $this->fixturesDir.'/base.css' => 'base.css',
            $this->fixturesDir.'/css' => 'css',
            $this->fixturesDir.'/js' => 'js',
        ), iterator_to_array($iterator));
    }

    public function testIterateRecursively()
    {
        $iterator = new \RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->fixturesDir, RecursiveDirectoryIterator::CURRENT_AS_FILE),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $this->assertSame(array(
            $this->fixturesDir.'/base.css' => 'base.css',
            $this->fixturesDir.'/css' => 'css',
            $this->fixturesDir.'/css/reset.css' => 'reset.css',
            $this->fixturesDir.'/css/style.css' => 'style.css',
            $this->fixturesDir.'/js' => 'js',
            $this->fixturesDir.'/js/script.js' => 'script.js',
        ), iterator_to_array($iterator));
    }

    public function testSeek()
    {
        $iterator = new RecursiveDirectoryIterator($this->fixturesDir, RecursiveDirectoryIterator::CURRENT_AS_FILE);

        $iterator->seek(0);
        $this->assertSame($this->fixturesDir.'/base.css', $iterator->key());
        $this->assertSame('base.css', $iterator->current());

        $iterator->seek(1);
        $this->assertSame($this->fixturesDir.'/css', $iterator->key());
        $this->assertSame('css', $iterator->current());

        $iterator->seek(2);
        $this->assertSame($this->fixturesDir.'/js', $iterator->key());
        $this->assertSame('js', $iterator->current());

        $iterator->seek(0);
        $this->assertSame($this->fixturesDir.'/base.css', $iterator->key());
        $this->assertSame('base.css', $iterator->current());
    }

    /**
     * @expectedException \Puli\Repository\Filesystem\FilesystemException
     */
    public function testFailIfNonExistingBaseDirectory()
    {
        new RecursiveDirectoryIterator($this->fixturesDir.'/foobar');
    }
}
