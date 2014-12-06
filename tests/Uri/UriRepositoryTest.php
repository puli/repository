<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Uri;

use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Tests\Resource\TestFile;
use Puli\Repository\Uri\UriRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UriRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new UriRepository();
    }

    public function provideValidSchemes()
    {
        return array(
            array('scheme'),
            array('psr4'), // alpha-numeric is supported
        );
    }

    public function provideInvalidSchemes()
    {
        return array(
            array(''),
            array(null),
            array(123),
            array('1foo'), // must start with a letter
            array('foo@'), // special characters are not supported
            array(new \stdClass()),
        );
    }

    /**
     * @dataProvider provideValidSchemes
     */
    public function testRegisterRepository($scheme)
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register($scheme, $repo);

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->get($scheme.':///path/to/resource'));
    }

    public function testRegisterRepositoryFactory()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme', function () use ($repo) {
            return $repo;
        });

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->get('scheme:///path/to/resource'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterExpectsValidRepositoryFactory()
    {
        $this->repo->register('scheme', 'foo');
    }

    /**
     * @dataProvider provideInvalidSchemes
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterExpectsValidScheme($scheme)
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register($scheme, $repo);
    }

    /**
     * @expectedException \Puli\Repository\Uri\RepositoryFactoryException
     */
    public function testRepositoryFactoryMustReturnRepository()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme', function () use ($repo) {
            return 'foo';
        });

        $this->repo->get('scheme:///path/to/resource');
    }

    public function testFirstRegisteredSchemeIsDefault()
    {
        $this->assertNull($this->repo->getDefaultScheme());

        $this->repo->register('first', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->assertSame('first', $this->repo->getDefaultScheme());

        $this->repo->register('second', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->assertSame('first', $this->repo->getDefaultScheme());

        $this->repo->unregister('second');
        $this->assertSame('first', $this->repo->getDefaultScheme());

        $this->repo->unregister('first');
        $this->assertNull($this->repo->getDefaultScheme());
    }

    public function testSetDefaultScheme()
    {
        $this->repo->register('first', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->repo->register('second', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));

        $this->repo->setDefaultScheme('second');

        $this->assertSame('second', $this->repo->getDefaultScheme());

        $this->repo->unregister('second');
        $this->assertNull($this->repo->getDefaultScheme());
    }

    /**
     * @expectedException \Puli\Repository\Uri\UnsupportedSchemeException
     */
    public function testSetDefaultSchemeFailsIfUnknownScheme()
    {
        $this->repo->setDefaultScheme('foobar');
    }

    public function testGetUsesDefaultSchemeIfPathGiven()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme1', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->repo->register('scheme2', $repo);
        $this->repo->setDefaultScheme('scheme2');

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->get('/path/to/resource'));
    }

    /**
     * @expectedException \Puli\Repository\Uri\UnsupportedSchemeException
     */
    public function testGetExpectsRegisteredScheme()
    {
        $this->repo->get('scheme:///path/to/resource');
    }

    /**
     * @expectedException \Puli\Repository\Uri\UnsupportedSchemeException
     */
    public function testGetCantUseUnregisteredScheme()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme', $repo);
        $this->repo->unregister('scheme');

        $this->repo->get('scheme:///path/to/resource');
    }

    public function testGetRegisteredSchemes()
    {
        $this->assertEquals(array(), $this->repo->getSupportedSchemes());

        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('resource', $repo);
        $this->assertEquals(array('resource'), $this->repo->getSupportedSchemes());

        $this->repo->register('namespace', $repo);
        $this->assertEquals(array('resource', 'namespace'), $this->repo->getSupportedSchemes());

        $this->repo->unregister('resource');
        $this->assertEquals(array('namespace'), $this->repo->getSupportedSchemes());

        $this->repo->unregister('namespace');
        $this->assertEquals(array(), $this->repo->getSupportedSchemes());
    }

    /**
     * @expectedException \Puli\Repository\Uri\InvalidUriException
     */
    public function testGetExpectsValidUri()
    {
        $this->repo->get('foo');
    }

    /**
     * @expectedException \Puli\Repository\Uri\InvalidUriException
     */
    public function testGetExpectsString()
    {
        $this->repo->get(new \stdClass());
    }

    public function testContains()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme', $repo);

        $repo->expects($this->at(0))
            ->method('contains')
            ->with('/path/to/resource-1')
            ->will($this->returnValue(true));
        $repo->expects($this->at(1))
            ->method('contains')
            ->with('/path/to/resource-2')
            ->will($this->returnValue(false));

        $this->assertTrue($this->repo->contains('scheme:///path/to/resource-1'));
        $this->assertFalse($this->repo->contains('scheme:///path/to/resource-2'));
    }

    public function testContainsUsesDefaultScheme()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme1', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->repo->register('scheme2', $repo);
        $this->repo->setDefaultScheme('scheme2');

        $repo->expects($this->once())
            ->method('contains')
            ->with('/path/to/resource-1')
            ->will($this->returnValue(true));

        $this->assertTrue($this->repo->contains('/path/to/resource-1'));
    }

    /**
     * @expectedException \Puli\Repository\Uri\InvalidUriException
     */
    public function testContainsExpectsValidUri()
    {
        $this->repo->contains('foo');
    }

    public function testFind()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme', $repo);

        $repo->expects($this->once())
            ->method('find')
            ->with('/path/to/res*')
            ->will($this->returnValue('RESULT'));

        $this->assertSame('RESULT', $this->repo->find('scheme:///path/to/res*'));
    }

    public function testFindUsesDefaultSchemeIfPathGiven()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme1', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->repo->register('scheme2', $repo);
        $this->repo->setDefaultScheme('scheme2');

        $repo->expects($this->once())
            ->method('find')
            ->with('/path/to/res*')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->find('/path/to/res*'));
    }

    /**
     * @expectedException \Puli\Repository\Uri\InvalidUriException
     */
    public function testFindExpectsValidUri()
    {
        $this->repo->find('foo');
    }

    public function testListDirectory()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme', $repo);

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path/to/dir')
            ->will($this->returnValue('RESULT'));

        $this->assertSame('RESULT', $this->repo->listDirectory('scheme:///path/to/dir'));
    }

    public function testListDirectoryUsesDefaultSchemeIfPathGiven()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('scheme1', $this->getMock('Puli\Repository\ResourceRepositoryInterface'));
        $this->repo->register('scheme2', $repo);
        $this->repo->setDefaultScheme('scheme2');

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path/to/dir')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->repo->listDirectory('/path/to/dir'));
    }

    /**
     * @expectedException \Puli\Repository\Uri\InvalidUriException
     */
    public function testListDirectoryExpectsValidUri()
    {
        $this->repo->listDirectory('foo');
    }

    public function testFindByTagChecksAllRepositories()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('resource', $repo1);
        $this->repo->register('namespace', $repo2);

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

    public function testGetTagsReturnsUnionFromAllRepositories()
    {
        $repo1 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $repo2 = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $this->repo->register('resource', $repo1);
        $this->repo->register('namespace', $repo2);

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
