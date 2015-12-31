<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests;

use PHPUnit_Framework_TestCase;
use Puli\Repository\NullRepository;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NullRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var NullRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new NullRepository();
    }

    public function testAdd()
    {
        $this->repo->add('/path', new TestFile());

        $this->assertFalse($this->repo->contains('/path'));
    }

    public function testRemove()
    {
        $this->assertFalse($this->repo->contains('/path'));

        $this->repo->remove('/path');

        $this->assertFalse($this->repo->contains('/path'));
    }

    public function testFind()
    {
        $this->repo->add('/path', new TestFile());

        $this->assertCount(0, $this->repo->find('/path'));
    }

    public function testListChildren()
    {
        $this->repo->add('/path', new TestDirectory(null, array(
            new TestFile('/path/file'),
        )));

        $this->assertCount(0, $this->repo->listChildren('/path'));
        $this->assertFalse($this->repo->hasChildren('/path'));
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetAlwaysThrowsException()
    {
        $this->repo->add('/path', new TestFile());

        $this->repo->get('/path');
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetStackAlwaysThrowsException()
    {
        $this->repo->add('/path', new TestFile());

        $this->repo->getStack('/path');
    }
}
