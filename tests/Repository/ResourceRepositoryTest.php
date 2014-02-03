<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Repository;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Tests\Locator\AbstractResourceLocatorTest;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepositoryTest extends AbstractResourceLocatorTest
{
    public function testAddFile()
    {
        $this->repo->add('/webmozart/puli/file1', __DIR__.'/Fixtures/dir1/file1');

        $file = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file->getPath());
    }

    public function testAddDirectory()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1', $dir->getPath());

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file1->getPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
    }

    public function testAddArray()
    {
        $this->repo->add('/webmozart/puli', array(
            __DIR__.'/Fixtures/dir1/file2',
            __DIR__.'/Fixtures/dir2/file1',
        ));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir2/file1', $file1->getPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
    }

    public function testAddPattern()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1/*');

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file1->getPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
    }

    public function testAddPatternInstance()
    {
        $this->repo->add('/webmozart/puli', new GlobPattern(__DIR__.'/Fixtures/dir1/*'));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file1->getPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
    }

    public function testAddArrayPattern()
    {
        $this->repo->add('/webmozart/puli', array(
            __DIR__.'/Fixtures/dir1/file2',
            __DIR__.'/Fixtures/dir2/*',
        ));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir2/file1', $file1->getPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsStringOrArray()
    {
        $this->repo->add('/webmozart/puli', 12345);
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testAddExpectsValidFilePath()
    {
        $this->repo->add('/webmozart/puli', '/foo/bar');
    }

    public function testAddTrimsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli/', __DIR__.'/Fixtures/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1', $dir->getPath());
    }

    public function provideDirectorySelector()
    {
        return array(
            array('/webmozart/puli'),
            array('/webmozart/pu*'),
            array(new GlobPattern('/webmozart/pu*')),
            array(array(
                '/webmozart/puli',
            )),
            array(array(
                '/webmozart/pu*',
            )),
            array(array(
                new GlobPattern('/webmozart/pu*'),
            )),
        );
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testRemoveMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @dataProvider provideDirectorySelector
     */
    public function testRemoveDirectory($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/');

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\RemovalNotAllowedException
     */
    public function testRemoveDoesNotRemoveRoot()
    {
        $this->repo->remove('/');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\RemovalNotAllowedException
     */
    public function testRemoveInterpretsConsecutiveSlashesAsRoot()
    {
        $this->repo->remove('///');
    }

    public function testTagOne()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $results = array($this->repo->get('/webmozart/puli/file1'));

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testTagMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag($selector, 'webmozart/tag');

        $results = array(
            $this->repo->get('/webmozart/puli/file1'),
            $this->repo->get('/webmozart/puli/file2'),
        );

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    public function testTagDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertEquals(array(), $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testTagExpectsValidPath()
    {
        $this->repo->tag('/foo/bar', 'webmozart/tag');
    }

    public function testUntagOne()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $tag1 = array($this->repo->get('/webmozart/puli/file2'));
        $tag2 = array($this->repo->get('/webmozart/puli/file1'));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    public function testUntagOneIgnoresIfNotTagged()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');
    }

    public function testUntagOneAllTags()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1');

        $tag1 = array($this->repo->get('/webmozart/puli/file2'));
        $tag2 = array();

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector, 'webmozart/tag1');

        $tag1 = array();
        $tag2 = array($this->repo->get('/webmozart/puli/file1'));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagManyIgnoresIfNotTagged($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->untag($selector, 'webmozart/tag1');
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagManyAllTags($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector);

        $tag1 = array();
        $tag2 = array();

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testUntagExpectsValidPath()
    {
        $this->repo->untag('/foo/bar', 'webmozart/tag');
    }

    public function testSetDefaultPatternClass()
    {
        $this->repo->setDefaultPatternClass('\Webmozart\Puli\Tests\Repository\Fixtures\CustomPattern');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDefaultPatternClassExpectsValidClass()
    {
        $this->repo->setDefaultPatternClass('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDefaultPatternClassExpectsImplementationOfPatternInterface()
    {
        $this->repo->setDefaultPatternClass('\stdClass');
    }

    protected function dumpLocator()
    {
        $this->locator = $this->repo;
    }
}
