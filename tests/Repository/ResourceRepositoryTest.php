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
use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Tests\Locator\AbstractResourceLocatorTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepositoryTest extends AbstractResourceLocatorTest
{
    public function testAddFile()
    {
        $this->repo->add('/webmozart/puli/file1', $this->fixturesDir.'/dir1/file1');

        $file = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    public function testAddFileConvertsIntoRealPath()
    {
        $this->repo->add('/webmozart/puli/file1', __DIR__.'/../Fixtures/dir1/file1');

        $file = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    public function testAddLink()
    {
        $this->repo->add('/webmozart/puli/file1-link', $this->fixturesDir.'/dir2/file1-link');

        $file = $this->repo->get('/webmozart/puli/file1-link');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1-link', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir2/file1-link', $file->getRealPath());
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\NoDirectoryException
     */
    public function testAddFileAsChildOfFile()
    {
        $this->repo->add('/webmozart/puli/file1', $this->fixturesDir.'/dir1/file1');
        $this->repo->add('/webmozart/puli/file1/file2', $this->fixturesDir.'/dir1/file2');
    }

    public function testAddDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $dir->getRealPath());

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file1->getRealPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file2->getRealPath());
    }

    public function testAddDot()
    {
        $this->repo->add('/webmozart/puli/file1/.', $this->fixturesDir.'/dir1/file1');

        $file = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    public function testAddDotDot()
    {
        $this->repo->add('/webmozart/puli/file1/..', $this->fixturesDir.'/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $dir->getRealPath());
    }

    public function testAddArray()
    {
        $this->repo->add('/webmozart/puli', array(
            $this->fixturesDir.'/dir1/file2',
            $this->fixturesDir.'/dir2/file1',
        ));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getPath());
        $this->assertEquals($this->fixturesDir.'/dir2/file1', $file1->getRealPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file2->getRealPath());
    }

    public function testAddPattern()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1/*');

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file1->getRealPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file2->getRealPath());
    }

    public function testAddPatternInstance()
    {
        $this->repo->add('/webmozart/puli', new GlobPattern($this->fixturesDir.'/dir1/*'));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file1->getRealPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file2->getRealPath());
    }

    public function testAddArrayPattern()
    {
        $this->repo->add('/webmozart/puli', array(
            $this->fixturesDir.'/dir1/file2',
            $this->fixturesDir.'/dir2/*',
        ));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getPath());
        $this->assertEquals($this->fixturesDir.'/dir2/file1', $file1->getRealPath());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file2->getRealPath());
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testAddExpectsValidFilePath()
    {
        $this->repo->add('/webmozart/puli', '/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonEmptyRepositoryPath()
    {
        $this->repo->add('', $this->fixturesDir.'/dir1');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonRootRepositoryPath()
    {
        $this->repo->add('/', $this->fixturesDir.'/dir1');
    }

    public function testAddTrimsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli/', $this->fixturesDir.'/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $dir->getRealPath());
    }

    public function testRemoveFile()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));
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
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

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
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/.');

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDotDot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->assertTrue($this->repo->contains('/'));
        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/..');

        $this->assertTrue($this->repo->contains('/'));
        $this->assertFalse($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/');

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testRemoveDoesNotRemoveRoot()
    {
        $this->repo->remove('/');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsNonEmptyPath()
    {
        $this->repo->remove('');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testRemoveInterpretsConsecutiveSlashesAsRoot()
    {
        $this->repo->remove('///');
    }

    public function testTagOne()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $results = new ResourceCollection(array($this->repo->get('/webmozart/puli/file1')));

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    public function testTagDot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/.', 'webmozart/tag');

        $results = new ResourceCollection(array($this->repo->get('/webmozart/puli')));

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    public function testTagDotDot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/..', 'webmozart/tag');

        $results = new ResourceCollection(array($this->repo->get('/webmozart')));

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testTagMany($selector)
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag($selector, 'webmozart/tag');

        $results = new ResourceCollection(array(
            $this->repo->get('/webmozart/puli/file1'),
            $this->repo->get('/webmozart/puli/file2'),
        ));

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    public function testTagDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertEquals(new ResourceCollection(), $this->repo->getByTag('webmozart/tag'));
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
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $tag1 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file2')));
        $tag2 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file1')));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    public function testUntagDot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/.', 'webmozart/tag1');

        $tag1 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file2')));
        $tag2 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file1')));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    public function testUntagDotDot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/..', 'webmozart/tag1');

        $tag1 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file2')));
        $tag2 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file1')));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    public function testUntagOneIgnoresIfNotTagged()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');
    }

    public function testUntagOneAllTags()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1');

        $tag1 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file2')));
        $tag2 = new ResourceCollection();

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagMany($selector)
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector, 'webmozart/tag1');

        $tag1 = new ResourceCollection();
        $tag2 = new ResourceCollection(array($this->repo->get('/webmozart/puli/file1')));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagManyIgnoresIfNotTagged($selector)
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->untag($selector, 'webmozart/tag1');
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagManyAllTags($selector)
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector);

        $tag1 = new ResourceCollection();
        $tag2 = new ResourceCollection();

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

    protected function dumpLocator()
    {
        $this->locator = $this->repo;
    }
}
