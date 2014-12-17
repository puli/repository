<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem\Iterator;

use PHPUnit_Framework_TestCase;
use Puli\Repository\Filesystem\Iterator\GlobIterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobIteratorTest extends PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = __DIR__.'/Fixtures';
    }

    public function testIterate()
    {
        $iterator = new GlobIterator($this->fixturesDir.'/*.css');

        $this->assertSame(array(
            $this->fixturesDir.'/base.css',
            $this->fixturesDir.'/css/reset.css',
            $this->fixturesDir.'/css/style.css',
        ), iterator_to_array($iterator));
    }

    public function testWildcardMayMatchZeroCharacters()
    {
        $iterator = new GlobIterator($this->fixturesDir.'/*css');

        $this->assertSame(array(
            $this->fixturesDir.'/base.css',
            $this->fixturesDir.'/css',
            $this->fixturesDir.'/css/reset.css',
            $this->fixturesDir.'/css/style.css',
        ), iterator_to_array($iterator));
    }

    public function testWildcardInRoot()
    {
        $iterator = new GlobIterator($this->fixturesDir.'/*');

        $this->assertSame(array(
            $this->fixturesDir.'/base.css',
            $this->fixturesDir.'/css',
            $this->fixturesDir.'/css/reset.css',
            $this->fixturesDir.'/css/style.css',
            $this->fixturesDir.'/js',
            $this->fixturesDir.'/js/script.js',
        ), iterator_to_array($iterator));
    }

    public function testNoMatches()
    {
        $iterator = new GlobIterator($this->fixturesDir.'/foo*');

        $this->assertSame(array(), iterator_to_array($iterator));
    }

    public function testNonExistingBaseDirectory()
    {
        $iterator = new GlobIterator($this->fixturesDir.'/foo/*');

        $this->assertSame(array(), iterator_to_array($iterator));
    }
}
