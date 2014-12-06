<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests;

use Puli\Repository\CompositeRepository;
use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CompositeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompositeRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new CompositeRepository();
    }

    public function provideValidMountPoints()
    {
        return array(
            array('/webmozart'),
            array('/webmozart/'),
        );
    }

    public function provideInvalidMountPoints()
    {
        return array(
            array(''),
            array(null),
            array(123),
            array('/'), // root cannot be mounted
            array('\\'),
            array(new \stdClass()),
        );
    }

    /**
     * @dataProvider provideValidMountPoints
     */
    public function testMountRepository($mountPoint)
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount($mountPoint, $repo);

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->get(rtrim($mountPoint, '/').'/path/to/resource'));
    }

    public function testMountRepositoryFactory()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', function () use ($repo) {
            return $repo;
        });

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->get('/webmozart/path/to/resource'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMountExpectsValidRepositoryFactory()
    {
        $this->repo->mount('/webmozart', 'foo');
    }

    /**
     * @dataProvider provideInvalidMountPoints
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testMountExpectsValidMountPoint($mountPoint)
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount($mountPoint, $repo);
    }

    /**
     * @expectedException \Puli\Repository\Uri\RepositoryFactoryException
     */
    public function testRepositoryFactoryMustReturnRepository()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', function () use ($repo) {
            return 'foo';
        });

        $this->repo->get('/webmozart/path/to/resource');
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testGetExpectsValidMountPoint()
    {
        $this->repo->get('/webmozart/path/to/resource');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testGetExpectsAbsolutePath()
    {
        $this->repo->get('webmozart');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testGetExpectsNonEmptyPath()
    {
        $this->repo->get('');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testGetExpectsStringPath()
    {
        $this->repo->get(new \stdClass());
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testUnmount()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo);
        $this->repo->unmount('/webmozart');

        $this->repo->get('/webmozart/path/to/resource');
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testUnmountWithTrailingSlash()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo);
        $this->repo->unmount('/webmozart/');

        $this->repo->get('/webmozart/path/to/resource');
    }

    public function testUnmountDoesNothingIfMountPointNotFound()
    {
        $this->repo->unmount('/webmozart');
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testUnmountExpectsAbsolutePath()
    {
        $this->repo->unmount('webmozart');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testUnmountExpectsNonEmptyPath()
    {
        $this->repo->unmount('');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testUnmountExpectsStringPath()
    {
        $this->repo->unmount(new \stdClass());
    }

    public function testContains()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo);

        $repo->expects($this->at(0))
            ->method('contains')
            ->with('/path/to/resource-1')
            ->will($this->returnValue(true));
        $repo->expects($this->at(1))
            ->method('contains')
            ->with('/path/to/resource-2')
            ->will($this->returnValue(false));

        $this->assertTrue($this->repo->contains('/webmozart/path/to/resource-1'));
        $this->assertFalse($this->repo->contains('/webmozart/path/to/resource-2'));
    }

    public function testContainsReturnsFalseIfMountPointNotFound()
    {
        $this->assertFalse($this->repo->contains('/webmozart'));
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testContainsExpectsAbsolutePath()
    {
        $this->repo->contains('webmozart');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testContainsExpectsNonEmptyPath()
    {
        $this->repo->contains('');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testContainsExpectsStringPath()
    {
        $this->repo->contains(new \stdClass());
    }

    public function testFind()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo);

        $repo->expects($this->once())
            ->method('find')
            ->with('/path/to/res*')
            ->will($this->returnValue('RESULT'));

        $this->assertSame('RESULT', $this->repo->find('/webmozart/path/to/res*'));
    }

    public function testFindReturnsEmptyCollectionIfMountPointNotFound()
    {
        $resources = $this->repo->find('/webmozart/path/to/res*');

        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollectionInterface', $resources);
        $this->assertCount(0, $resources);
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testFindExpectsAbsolutePath()
    {
        $this->repo->find('webmozart');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testFindExpectsNonEmptyPath()
    {
        $this->repo->find('');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testFindExpectsStringPath()
    {
        $this->repo->find(new \stdClass());
    }

    public function testListDirectory()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo);

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path/to/dir')
            ->will($this->returnValue('RESULT'));

        $this->assertSame('RESULT', $this->repo->listDirectory('/webmozart/path/to/dir'));
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testListDirectoryExpectsValidMountPoint()
    {
        $this->repo->listDirectory('/webmozart/path/to/dir');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testListDirectoryExpectsAbsolutePath()
    {
        $this->repo->listDirectory('webmozart');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testListDirectoryExpectsNonEmptyPath()
    {
        $this->repo->listDirectory('');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testListDirectoryExpectsStringPath()
    {
        $this->repo->listDirectory(new \stdClass());
    }

    public function testFindByTagChecksAllRepositories()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo1);
        $this->repo->mount('/puli', $repo2);

        $resources = new ResourceCollection(array(
            new TestFile('foo'),
            new TestFile('bar'),
        ));

        $repo1->expects($this->once())
            ->method('findByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array($resources[0]))));
        $repo2->expects($this->once())
            ->method('findByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array($resources[1]))));

        $this->assertEquals(
            $resources,
            $this->repo->findByTag('acme/tag')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindByTagExpectsNonEmptyPath()
    {
        $this->repo->findByTag('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindByTagExpectsStringPath()
    {
        $this->repo->findByTag(new \stdClass());
    }

    public function testGetTagsReturnsUnionFromAllRepositories()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo1);
        $this->repo->mount('/puli', $repo2);

        $repo1->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue(array('foo')));
        $repo2->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue(array('foo', 'bar')));

        $this->assertEquals(
            array('foo', 'bar'),
            $this->repo->getTags()
        );
    }
}
