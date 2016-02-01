<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Discovery;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Discovery\Binding\ClassBinding;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Discovery\ResourceBinding;
use Puli\Repository\Discovery\ResourceBindingInitializer;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceBindingInitializerTest extends PHPUnit_Framework_TestCase
{
    const RESOURCE_BINDING = 'Puli\Repository\Discovery\ResourceBinding';

    const SUB_RESOURCE_BINDING = 'Puli\Repository\Tests\Discovery\Fixtures\SubResourceBinding';

    const CLASS_BINDING = 'Puli\Discovery\Binding\ClassBinding';

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ResourceRepository
     */
    private $repo;

    /**
     * @var ResourceBindingInitializer
     */
    private $initializer;

    protected function setUp()
    {
        $this->repo = $this->getMock('Puli\Repository\Api\ResourceRepository');
        $this->initializer = new ResourceBindingInitializer($this->repo);
    }

    public function testAcceptsBinding()
    {
        $this->assertTrue($this->initializer->acceptsBinding(self::RESOURCE_BINDING));
        $this->assertFalse($this->initializer->acceptsBinding(self::CLASS_BINDING));
        $this->assertTrue($this->initializer->acceptsBinding(new ResourceBinding('/path', 'acme/foo')));
        $this->assertFalse($this->initializer->acceptsBinding(new ClassBinding(__CLASS__, 'acme/foo')));
    }

    public function testAcceptsBindingAcceptsSubClasses()
    {
        $this->assertTrue($this->initializer->acceptsBinding(self::SUB_RESOURCE_BINDING));
    }

    public function testGetAcceptedBindingClass()
    {
        $this->assertSame(self::RESOURCE_BINDING, $this->initializer->getAcceptedBindingClass());
    }

    public function testInitializeBinding()
    {
        $binding = $this->getMockBuilder(self::RESOURCE_BINDING)
            ->disableOriginalConstructor()
            ->getMock();

        $binding->expects($this->once())
            ->method('setRepository')
            ->with($this->repo);

        $this->initializer->initializeBinding($binding);
    }

    public function testInitializeBindingOfSubClass()
    {
        $binding = $this->getMockBuilder(self::SUB_RESOURCE_BINDING)
            ->disableOriginalConstructor()
            ->getMock();

        $binding->expects($this->once())
            ->method('setRepository')
            ->with($this->repo);

        $this->initializer->initializeBinding($binding);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInitializeBindingFailsIfInvalidArgument()
    {
        $this->initializer->initializeBinding(new ClassBinding(__CLASS__, 'acme/foo'));
    }
}
