<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Locator;

use Webmozart\Puli\Locator\UriLocator;
use Webmozart\Puli\Resource\FileResource;
use Webmozart\Puli\Resource\ResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UriLocator
     */
    private $uriLocator;

    protected function setUp()
    {
        $this->uriLocator = new UriLocator();
    }

    public function testRegisterLocator()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('scheme', $locator);

        $locator->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->uriLocator->get('scheme:///path/to/resource'));
    }

    public function testRegisterLocatorFactory()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('scheme', function () use ($locator) {
            return $locator;
        });

        $locator->expects($this->once())
            ->method('get')
            ->with('/path/to/resource')
            ->will($this->returnValue('RESULT'));

        $this->assertEquals('RESULT', $this->uriLocator->get('scheme:///path/to/resource'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterExpectsValidLocatorFactory()
    {
        $this->uriLocator->register('scheme', 'foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterExpectsValidScheme()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register(new \stdClass(), $locator);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterExpectsAlphabeticScheme()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('foo1', $locator);
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\LocatorFactoryException
     */
    public function testLocatorFactoryMustReturnLocator()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('scheme', function () use ($locator) {
            return 'foo';
        });

        $this->uriLocator->get('scheme:///path/to/resource');
        $this->uriLocator->get('scheme:///path/to/resource');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\SchemeNotSupportedException
     */
    public function testGetExpectsRegisteredScheme()
    {
        $this->uriLocator->get('scheme:///path/to/resource');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\SchemeNotSupportedException
     */
    public function testGetCantUseUnregisteredScheme()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('scheme', $locator);
        $this->uriLocator->unregister('scheme');

        $this->uriLocator->get('scheme:///path/to/resource');
    }

    public function testGetRegisteredSchemes()
    {
        $this->assertEquals(array(), $this->uriLocator->getRegisteredSchemes());

        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('resource', $locator);
        $this->assertEquals(array('resource'), $this->uriLocator->getRegisteredSchemes());

        $this->uriLocator->register('namespace', $locator);
        $this->assertEquals(array('resource', 'namespace'), $this->uriLocator->getRegisteredSchemes());

        $this->uriLocator->unregister('resource');
        $this->assertEquals(array('namespace'), $this->uriLocator->getRegisteredSchemes());

        $this->uriLocator->unregister('namespace');
        $this->assertEquals(array(), $this->uriLocator->getRegisteredSchemes());
    }

    /**
     * @expectedException \Webmozart\Puli\Uri\InvalidUriException
     */
    public function testGetExpectsValidUri()
    {
        $this->uriLocator->get('foo');
    }

    public function testContains()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('scheme', $locator);

        $locator->expects($this->at(0))
            ->method('contains')
            ->with('/path/to/resource-1')
            ->will($this->returnValue(true));
        $locator->expects($this->at(1))
            ->method('contains')
            ->with('/path/to/resource-2')
            ->will($this->returnValue(false));

        $this->assertTrue($this->uriLocator->contains('scheme:///path/to/resource-1'));
        $this->assertFalse($this->uriLocator->contains('scheme:///path/to/resource-2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Uri\InvalidUriException
     */
    public function testContainsExpectsValidUri()
    {
        $this->uriLocator->contains('foo');
    }

    public function testListDirectory()
    {
        $locator = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('scheme', $locator);

        $resources = new ResourceCollection(array(
            new FileResource('foo'),
            new FileResource('bar'),
        ));

        $locator->expects($this->once())
            ->method('listDirectory')
            ->with('/path/to/resource')
            ->will($this->returnValue($resources));

        $this->assertEquals(
            $resources,
            $this->uriLocator->listDirectory('scheme:///path/to/resource')
        );
    }

    /**
     * @expectedException \Webmozart\Puli\Uri\InvalidUriException
     */
    public function testListDirectoryExpectsValidUri()
    {
        $this->uriLocator->listDirectory('foo');
    }

    public function testGetByTagChecksAllLocators()
    {
        $locator1 = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');
        $locator2 = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('resource', $locator1);
        $this->uriLocator->register('namespace', $locator2);

        $resources = new ResourceCollection(array(
            new FileResource('foo'),
            new FileResource('bar'),
        ));

        $locator1->expects($this->once())
            ->method('getByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array($resources[0]))));
        $locator2->expects($this->once())
            ->method('getByTag')
            ->with('acme/tag')
            ->will($this->returnValue(new ResourceCollection(array($resources[1]))));

        $this->assertEquals(
            $resources,
            $this->uriLocator->getByTag('acme/tag')
        );
    }

    public function testGetTagsReturnsUnionFromAllLocators()
    {
        $locator1 = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');
        $locator2 = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');

        $this->uriLocator->register('resource', $locator1);
        $this->uriLocator->register('namespace', $locator2);

        $locator1->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue(array('foo')));
        $locator2->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue(array('foo', 'bar')));

        $this->assertEquals(
            array('foo', 'bar'),
            $this->uriLocator->getTags()
        );
    }
}
