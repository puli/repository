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
        $resource = new TestFile('/path/to/resource');
        $resource->attachTo($repo);
        $mountedPath = rtrim($mountPoint, '/').'/path/to/resource';

        $this->repo->mount($mountPoint, $repo);

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue($resource));

        $expected = $resource->createReference($mountedPath);

        $this->assertEquals($expected, $this->repo->get($mountedPath));
    }

    public function testGetPrefersMostSpecificMountPoint()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $resource1 = new TestFile('/resource1');
        $resource1->attachTo($repo1);
        $resource2 = new TestFile('/resource2');
        $resource2->attachTo($repo2);

        $this->repo->mount('/', $repo1);
        $this->repo->mount('/app', $repo2);

        $repo1->expects($this->once())
            ->method('get')
            ->with('/resource1')
            ->will($this->returnValue($resource1));
        $repo2->expects($this->once())
            ->method('get')
            ->with('/resource2')
            ->will($this->returnValue($resource2));

        $result1 = $this->repo->get('/resource1');

        $this->assertSame($resource1, $result1);

        $result2 = $this->repo->get('/app/resource2');

        $this->assertEquals('/app/resource2', $result2->getPath());
        $this->assertEquals('/resource2', $result2->getRepositoryPath());
        $this->assertSame($repo2, $result2->getRepository());
    }

    public function testMountRepositoryFactory()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $resource = new TestFile('/path/to/resource');
        $resource->attachTo($repo);

        $this->repo->mount('/webmozart', function () use ($repo) {
            return $repo;
        });

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue($resource));

        $expected = $resource->createReference('/webmozart/path/to/resource');

        $this->assertEquals($expected, $this->repo->get('/webmozart/path/to/resource'));
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

    public function testContainsPrefersMostSpecificMountPoint()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/', $repo1);
        $this->repo->mount('/app', $repo2);

        $repo1->expects($this->once())
            ->method('contains')
            ->with('/resource-1')
            ->will($this->returnValue(true));
        $repo2->expects($this->once())
            ->method('contains')
            ->with('/resource-2')
            ->will($this->returnValue(false));

        $this->assertTrue($this->repo->contains('/resource-1'));
        $this->assertFalse($this->repo->contains('/app/resource-2'));
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
        $resource1 = new TestFile('/path/to/res1');
        $resource2 = new TestFile('/path/to/res2');

        $this->repo->mount('/webmozart', $repo);

        $repo->expects($this->once())
            ->method('find')
            ->with('/path/to/res*')
            ->will($this->returnValue(new ResourceCollection(array(
                $resource1,
                $resource2,
            ))));

        $expected = new ResourceCollection(array(
            $resource1->createReference('/webmozart/path/to/res1'),
            $resource2->createReference('/webmozart/path/to/res2'),
        ));

        $this->assertEquals($expected, $this->repo->find('/webmozart/path/to/res*'));
    }

    public function testFindPrefersMostSpecificMountPoint()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $resource1 = new TestFile('/res1');
        $resource2 = new TestFile('/res2');

        $this->repo->mount('/', $repo1);
        $this->repo->mount('/app', $repo2);

        $repo1->expects($this->once())
            ->method('find')
            ->with('/res1*')
            ->will($this->returnValue(new ResourceCollection(array($resource1))));
        $repo2->expects($this->once())
            ->method('find')
            ->with('/res2*')
            ->will($this->returnValue(new ResourceCollection(array($resource2))));

        $expected1 = new ResourceCollection(array($resource1));
        $expected2 = new ResourceCollection(array($resource2->createReference('/app/res2')));

        $this->assertEquals($expected1, $this->repo->find('/res1*'));
        $this->assertEquals($expected2, $this->repo->find('/app/res2*'));
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
        $resource1 = new TestFile('/path/to/dir/file1');
        $resource2 = new TestFile('/path/to/dir/file2');

        $this->repo->mount('/webmozart', $repo);

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path/to/dir')
            ->will($this->returnValue(new ResourceCollection(array(
                $resource1,
                $resource2,
            ))));

        $expected = new ResourceCollection(array(
            $resource1->createReference('/webmozart/path/to/dir/file1'),
            $resource2->createReference('/webmozart/path/to/dir/file2'),
        ));

        $this->assertEquals($expected, $this->repo->listDirectory('/webmozart/path/to/dir'));
    }

    public function testListRootDirectory()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $resource1 = new TestFile('/path/to/dir/file1');
        $resource2 = new TestFile('/path/to/dir/file2');

        $this->repo->mount('/', $repo);

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path/to/dir')
            ->will($this->returnValue(new ResourceCollection(array(
                $resource1,
                $resource2,
            ))));

        $expected = new ResourceCollection(array(
            $resource1,
            $resource2,
        ));

        $this->assertEquals($expected, $this->repo->listDirectory('/path/to/dir'));
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
        $resource1 = new TestFile('/file1');
        $resource2 = new TestFile('/file2');

        $this->repo->mount('/webmozart', $repo1);
        $this->repo->mount('/puli', $repo2);

        $repo1->expects($this->once())
            ->method('findByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array($resource1))));
        $repo2->expects($this->once())
            ->method('findByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array($resource2))));

        // Result is sorted
        $expected = new ResourceCollection(array(
            $resource2->createReference('/puli/file2'),
            $resource1->createReference('/webmozart/file1'),
        ));

        $this->assertEquals($expected, $this->repo->findByTag('acme/tag'));
    }

    public function testFindByTagEliminatesOvershadowedTags()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $resource1 = new TestFile('/file1');
        $resource2 = new TestFile('/puli/file2'); // overshadowed by other mount
        $resource3 = new TestFile('/file3');

        $this->repo->mount('/', $repo1);
        $this->repo->mount('/puli', $repo2);

        $repo1->expects($this->once())
            ->method('findByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array(
                $resource1,
                $resource2, // overshadowed by other mount
            ))));
        $repo2->expects($this->once())
            ->method('findByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array(
                $resource3,
            ))));

        $expected = new ResourceCollection(array(
            $resource1->createReference('/file1'),
            $resource3->createReference('/puli/file3'),
        ));

        $this->assertEquals($expected, $this->repo->findByTag('acme/tag'));
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

    public function testGetTagsReturnsSortedUnionFromAllRepositories()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->mount('/webmozart', $repo1);
        $this->repo->mount('/puli', $repo2);

        $repo1->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue(array('foo', 'zong')));
        $repo2->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue(array('foo', 'bar')));

        $this->assertEquals(
            array('bar', 'foo', 'zong'),
            $this->repo->getTags()
        );
    }
}
