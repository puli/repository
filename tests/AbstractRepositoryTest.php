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
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param PuliResource $root
     *
     * @return ResourceRepository
     */
    abstract protected function createPrefilledRepository(PuliResource $root);

    /**
     * @param string $path
     * @param string $body
     *
     * @return TestFile
     */
    protected function createFile($path = null, $body = TestFile::BODY)
    {
        return new TestFile($path, $body);
    }

    /**
     * @param string $path
     * @param array  $children
     *
     * @return TestDirectory
     */
    protected function createDirectory($path = null, array $children = array())
    {
        return new TestDirectory($path, $children);
    }

    /**
     * Build the real backend structure.
     *
     * @param PuliResource $root
     *
     * @return PuliResource
     */
    protected function buildStructure(PuliResource $root)
    {
        return $root;
    }

    protected function pass()
    {
        $this->assertTrue(true);
    }

    public function testContainsPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $this->assertTrue($repo->contains('/'));
        $this->assertTrue($repo->contains('/.'));
        $this->assertTrue($repo->contains('/..'));
        $this->assertFalse($repo->contains('/webmozart'));
        $this->assertFalse($repo->contains('/webmozart/.'));
        $this->assertTrue($repo->contains('/webmozart/..'));
        $this->assertFalse($repo->contains('/./webmozart'));
        $this->assertFalse($repo->contains('/../webmozart'));
        $this->assertFalse($repo->contains('/webmozart/../webmozart'));
        $this->assertFalse($repo->contains('/webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/puli/.'));
        $this->assertFalse($repo->contains('/webmozart/puli/..'));
        $this->assertFalse($repo->contains('/webmozart/./puli'));
        $this->assertFalse($repo->contains('/webmozart/././puli'));
        $this->assertFalse($repo->contains('/webmozart/../webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/../../webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/../puli'));
        $this->assertFalse($repo->contains('/webmozart/./../puli'));
        $this->assertFalse($repo->contains('/webmozart/.././puli'));
        $this->assertFalse($repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($repo->contains('/webmozart/puli/file1/.'));
        $this->assertFalse($repo->contains('/webmozart/puli/file1/..'));
        $this->assertFalse($repo->contains('/webmozart/puli/file2'));
        $this->assertFalse($repo->contains('/webmozart/puli/file2/.'));
        $this->assertFalse($repo->contains('/webmozart/puli/file2/..'));

        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file1'),
                    $this->createFile('/file2'),
                )),
            )),
        ))));

        $this->assertTrue($repo->contains('/'));
        $this->assertTrue($repo->contains('/.'));
        $this->assertTrue($repo->contains('/..'));
        $this->assertTrue($repo->contains('/webmozart'));
        $this->assertTrue($repo->contains('/webmozart/.'));
        $this->assertTrue($repo->contains('/webmozart/..'));
        $this->assertTrue($repo->contains('/./webmozart'));
        $this->assertTrue($repo->contains('/../webmozart'));
        $this->assertTrue($repo->contains('/webmozart/puli'));
        $this->assertTrue($repo->contains('/webmozart/puli/.'));
        $this->assertTrue($repo->contains('/webmozart/puli/..'));
        $this->assertTrue($repo->contains('/webmozart/./puli'));
        $this->assertTrue($repo->contains('/webmozart/././puli'));
        $this->assertTrue($repo->contains('/webmozart/../webmozart/puli'));
        $this->assertTrue($repo->contains('/webmozart/../../webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/../puli'));
        $this->assertFalse($repo->contains('/webmozart/./../puli'));
        $this->assertFalse($repo->contains('/webmozart/.././puli'));
        $this->assertTrue($repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($repo->contains('/webmozart/puli/file1/.'));
        $this->assertTrue($repo->contains('/webmozart/puli/file1/..'));
        $this->assertTrue($repo->contains('/webmozart/puli/file2'));
        $this->assertTrue($repo->contains('/webmozart/puli/file2/.'));
        $this->assertTrue($repo->contains('/webmozart/puli/file2/..'));
    }

    public function testContainsPattern()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $this->assertFalse($repo->contains('/webmozart/**/*'));
        $this->assertFalse($repo->contains('/webmozart/file*'));
        $this->assertFalse($repo->contains('/webmozart/puli/file*'));
        $this->assertFalse($repo->contains('/webmozart/**/file*'));

        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file1'),
                    $this->createFile('/file2'),
                )),
            )),
        ))));

        $this->assertTrue($repo->contains('/**/*'));
        $this->assertTrue($repo->contains('/webmozart/**/*'));
        $this->assertFalse($repo->contains('/webmozart/file*'));
        $this->assertTrue($repo->contains('/webmozart/puli/file*'));
        $this->assertTrue($repo->contains('/**/file*'));
        $this->assertTrue($repo->contains('/webmozart/**/file*'));
    }

    public function testContainsDiscardsTrailingSlash()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $this->assertTrue($repo->contains('/webmozart/'));
    }

    public function testContainsInterpretsConsecutiveSlashesAsRoot()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $this->assertTrue($repo->contains('///'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainsExpectsAbsolutePath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $repo->contains('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainsExpectsNonEmptyPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->contains('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainsExpectsStringPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->contains(new \stdClass());
    }

    public function testGetResource()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli'),
            )),
        ))));

        $resource = $repo->get('/webmozart');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\PuliResource', $resource);
        $this->assertSame('/webmozart', $resource->getPath());
        $this->assertSame($repo, $resource->getRepository());
        $this->assertTrue($resource->hasChildren());
    }

    public function testGetBodyResource()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file'),
                )),
            )),
        ))));

        $resource = $repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $resource);
        $this->assertSame('/webmozart/puli/file', $resource->getPath());
        $this->assertSame($repo, $resource->getRepository());
        $this->assertFalse($resource->hasChildren());
    }

    public function testGetDiscardsTrailingSlash()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $this->assertEquals($repo->get('/webmozart'), $repo->get('/webmozart/'));
    }

    public function testGetInterpretsConsecutiveSlashesAsRoot()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $this->assertEquals($repo->get('/'), $repo->get('///'));
    }

    public function testGetCanonicalizesFilePaths()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file'),
                )),
            )),
        ))));

        $this->assertEquals($repo->get('/webmozart/puli/file'), $repo->get('/webmozart/puli/../puli/./file'));
    }

    public function testGetCanonicalizesDirectoryPaths()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createDirectory('/dir'),
                )),
            )),
        ))));

        $this->assertEquals($repo->get('/webmozart/puli/dir'), $repo->get('/webmozart/puli/../puli/dir'));
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetExpectsExistingResource()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->get('/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetExpectsAbsolutePath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $repo->get('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetExpectsNonEmptyPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->get('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetExpectsStringPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->get(new \stdClass());
    }

    public function testGetDotInDirectory()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $this->assertEquals($repo->get('/webmozart'), $repo->get('/webmozart/.'));
    }

    public function testGetDotInFile()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file1'),
                )),
            )),
        ))));

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $this->assertEquals($repo->get('/webmozart/puli/file1'), $repo->get('/webmozart/puli/file1/.'));
    }

    public function testGetDotInRoot()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $this->assertEquals($repo->get('/'), $repo->get('/.'));
    }

    public function testGetDotDotInDirectory()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli'),
            )),
        ))));

        $this->assertEquals($repo->get('/webmozart'), $repo->get('/webmozart/puli/..'));
    }

    public function testGetDotDotInFile()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file'),
                )),
            )),
        ))));

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $this->assertEquals($repo->get('/webmozart/puli'), $repo->get('/webmozart/puli/file1/..'));
    }

    public function testGetDotDotInRoot()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $this->assertEquals($repo->get('/'), $repo->get('/..'));
    }

    public function testHasChildren()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/.dotfile'),
                    $this->createFile('/foo'),
                    $this->createFile('/bar'),
                    $this->createDirectory('/dir'),
                )),
            )),
        ))));

        $this->assertTrue($repo->hasChildren('/'));
        $this->assertTrue($repo->hasChildren('/webmozart'));
        $this->assertTrue($repo->hasChildren('/webmozart/puli'));
        $this->assertFalse($repo->hasChildren('/webmozart/puli/.dotfile'));
        $this->assertFalse($repo->hasChildren('/webmozart/puli/foo'));
        $this->assertFalse($repo->hasChildren('/webmozart/puli/bar'));
        $this->assertFalse($repo->hasChildren('/webmozart/puli/dir'));
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testHasChildrenExpectsExistingResource()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->hasChildren('/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHasChildrenExpectsAbsolutePath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $repo->hasChildren('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHasChildrenExpectsNonEmptyPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->hasChildren('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHasChildrenExpectsStringPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->hasChildren(new \stdClass());
    }

    public function testListChildren()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/.dotfile'),
                    $this->createFile('/foo'),
                    $this->createFile('/bar'),
                    $this->createDirectory('/dir', array(
                        // Nest another directory which matches the regex
                        // /webmozart/puli/[^/]+$
                        $this->createDirectory('/webmozart', array(
                            $this->createDirectory('/puli', array(
                                $this->createFile('/file'),
                            )),
                        )),
                    )),
                )),
            )),
        ))));

        $resources = $repo->listChildren('/webmozart/puli');

        $this->assertCount(4, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/webmozart/puli/.dotfile'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart/puli/bar'), $resources[1]);
        $this->assertEquals($repo->get('/webmozart/puli/dir'), $resources[2]);
        $this->assertEquals($repo->get('/webmozart/puli/foo'), $resources[3]);
    }

    public function testListChildrenReturnsEmptyCollectionForFiles()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/foo'),
                )),
            )),
        ))));

        $resources = $repo->listChildren('/webmozart/puli/foo');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
    }

    public function testListRoot()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
            $this->createDirectory('/acme'),
        ))));

        $resources = $repo->listChildren('/');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/acme'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart'), $resources[1]);
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testListChildrenExpectsExistingResource()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->listChildren('/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListChildrenExpectsAbsolutePath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $repo->listChildren('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListChildrenExpectsNonEmptyPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->listChildren('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListChildrenExpectsStringPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->listChildren(new \stdClass());
    }

    public function testFind()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/.dotfoo'),
                    $this->createFile('/foo'),
                    $this->createFile('/bar'),
                    $this->createDirectory('/dirfoo'),
                )),
            )),
        ))));

        $resources = $repo->find('/webmozart/**/*foo');

        $this->assertCount(3, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/webmozart/puli/.dotfoo'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart/puli/dirfoo'), $resources[1]);
        $this->assertEquals($repo->get('/webmozart/puli/foo'), $resources[2]);
    }

    public function testFindBrackets()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/.dotfoo'),
                    $this->createFile('/foo'),
                    $this->createFile('/bar'),
                    $this->createDirectory('/dirfoo'),
                )),
            )),
        ))));

        $resources = $repo->find('/webmozart/puli/{foo,bar}');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/webmozart/puli/bar'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart/puli/foo'), $resources[1]);
    }

    public function testFindFull()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/.dotfoo'),
                    $this->createFile('/foo'),
                    $this->createFile('/bar'),
                    $this->createDirectory('/dirfoo'),
                )),
            )),
        ))));

        $resources = $repo->find('/webmozart/**/*{foo,bar}');

        $this->assertCount(4, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/webmozart/puli/.dotfoo'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart/puli/bar'), $resources[1]);
        $this->assertEquals($repo->get('/webmozart/puli/dirfoo'), $resources[2]);
        $this->assertEquals($repo->get('/webmozart/puli/foo'), $resources[3]);
    }

    public function testFindFile()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file'),
                )),
            )),
        ))));

        $resources = $repo->find('/webmozart/puli/file');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        $this->assertEquals($repo->get('/webmozart/puli/file'), $resources[0]);
    }

    public function testFindDirectory()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart'),
        ))));

        $resources = $repo->find('/webmozart');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        $this->assertEquals($repo->get('/webmozart'), $resources[0]);
    }

    public function testFindCanonicalizesGlob()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file1'),
                )),
            )),
        ))));

        $resources = $repo->find('/webmozart/puli/../puli/./**');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
        $this->assertEquals($repo->get('/webmozart/puli/file1'), $resources[0]);
    }

    public function testFindNoMatches()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $resources = $repo->find('/foo/**');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $resources);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindExpectsAbsolutePath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->find('*');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindExpectsNonEmptyPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->find('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindExpectsStringPath()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/')));

        $repo->find(new \stdClass());
    }

    public function testChangeStreamGetStack()
    {
        $repo = $this->createPrefilledRepository($this->buildStructure($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createDirectory('/puli', array(
                    $this->createFile('/file1'),
                    $this->createFile('/file2'),
                )),
            )),
        ))));

        $stack = $repo->getStack('/webmozart/puli/file1');

        $this->assertInstanceOf('Puli\Repository\ChangeStream\ResourceStack', $stack);
        $this->assertCount(1, $stack);
        $this->assertEquals('/webmozart/puli/file1', $stack->getFirstVersion()->getPath());
        $this->assertEquals('/webmozart/puli/file1', $stack->getVersion(0)->getPath());
        $this->assertEquals('/webmozart/puli/file1', $stack->getCurrentVersion()->getPath());
        $this->assertEquals(array(0), $stack->getAvailableVersions());
    }
}
