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

use Puli\Discovery\Test\AbstractBindingTest;
use Puli\Repository\Discovery\ResourceBinding;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceBindingTest extends AbstractBindingTest
{
    protected function createBinding($typeName, array $parameterValues = array())
    {
        return new ResourceBinding('/path/*', $typeName, $parameterValues, 'glob');
    }

    public function testCreateWithQuery()
    {
        $binding = new ResourceBinding('/path/*', 'acme/foo', array(), 'glob');

        $this->assertSame('/path/*', $binding->getQuery());
        $this->assertSame('glob', $binding->getLanguage());
        $this->assertSame('acme/foo', $binding->getTypeName());
    }

    public function testGetResources()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');
        $binding = new ResourceBinding('/path/*', 'acme/foo', array(), 'language');

        $repo->expects($this->once())
            ->method('find')
            ->with('/path/*', 'language')
            ->willReturn('RESULT');

        $binding->setRepository($repo);

        $this->assertSame('RESULT', $binding->getResources());
    }

    /**
     * @expectedException \Puli\Discovery\Api\Binding\Initializer\NotInitializedException
     */
    public function testGetResourcesFailsIfNotSet()
    {
        $binding = new ResourceBinding('/path/*', 'acme/foo', array(), 'language');

        $binding->getResources();
    }
}
