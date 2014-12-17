<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource\Iterator;

use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\Iterator\RecursiveResourceIteratorIterator;
use Puli\Repository\Resource\Iterator\ResourceCollectionIterator;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceCollectionIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultIteration()
    {
        $collection = new ArrayResourceCollection(array(
            $dir1 = new TestDirectory('/webmozart', array(
                $dir11 = new TestDirectory('/webmozart/puli', array(
                    $dir111 = new TestDirectory('/webmozart/puli/config', array(
                        $file1111 = new TestFile('/webmozart/puli/config/config.yml'),
                        $file1112 = new TestFile('/webmozart/puli/config/routing.yml'),
                    )),
                    $dir112 = new TestDirectory('/webmozart/puli/css', array(
                        $file1121 = new TestFile('/webmozart/puli/css/style.css'),
                    )),
                    $file113 = new TestFile('/webmozart/puli/installer.json'),
                ))
            )),
        ));

        $iterator = new RecursiveResourceIteratorIterator(
            new ResourceCollectionIterator($collection),
            RecursiveResourceIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => $dir1,
            '/webmozart/puli' => $dir11,
            '/webmozart/puli/config' => $dir111,
            '/webmozart/puli/config/config.yml' => $file1111,
            '/webmozart/puli/config/routing.yml' => $file1112,
            '/webmozart/puli/css' => $dir112,
            '/webmozart/puli/css/style.css' => $file1121,
            '/webmozart/puli/installer.json' => $file113,
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testCurrentAsResource()
    {
        $collection = new ArrayResourceCollection(array(
            $dir1 = new TestDirectory('/webmozart', array(
                $dir11 = new TestDirectory('/webmozart/puli', array(
                    $dir111 = new TestDirectory('/webmozart/puli/config', array(
                        $file1111 = new TestFile('/webmozart/puli/config/config.yml'),
                        $file1112 = new TestFile('/webmozart/puli/config/routing.yml'),
                    )),
                    $dir112 = new TestDirectory('/webmozart/puli/css', array(
                        $file1121 = new TestFile('/webmozart/puli/css/style.css'),
                    )),
                    $file113 = new TestFile('/webmozart/puli/installer.json'),
                ))
            )),
        ));

        $iterator = new RecursiveResourceIteratorIterator(
            new ResourceCollectionIterator(
                $collection,
                ResourceCollectionIterator::CURRENT_AS_RESOURCE
            ),
            RecursiveResourceIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => $dir1,
            '/webmozart/puli' => $dir11,
            '/webmozart/puli/config' => $dir111,
            '/webmozart/puli/config/config.yml' => $file1111,
            '/webmozart/puli/config/routing.yml' => $file1112,
            '/webmozart/puli/css' => $dir112,
            '/webmozart/puli/css/style.css' => $file1121,
            '/webmozart/puli/installer.json' => $file113,
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testCurrentAsPath()
    {
        $collection = new ArrayResourceCollection(array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestDirectory('/webmozart/puli/config', array(
                        new TestFile('/webmozart/puli/config/config.yml'),
                        new TestFile('/webmozart/puli/config/routing.yml'),
                    )),
                    new TestDirectory('/webmozart/puli/css', array(
                        new TestFile('/webmozart/puli/css/style.css'),
                    )),
                    new TestFile('/webmozart/puli/installer.json'),
                ))
            )),
        ));

        $iterator = new RecursiveResourceIteratorIterator(
            new ResourceCollectionIterator(
                $collection,
                ResourceCollectionIterator::CURRENT_AS_PATH
            ),
            RecursiveResourceIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => '/webmozart',
            '/webmozart/puli' => '/webmozart/puli',
            '/webmozart/puli/config' => '/webmozart/puli/config',
            '/webmozart/puli/config/config.yml' => '/webmozart/puli/config/config.yml',
            '/webmozart/puli/config/routing.yml' => '/webmozart/puli/config/routing.yml',
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
            '/webmozart/puli/installer.json' => '/webmozart/puli/installer.json',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testCurrentAsName()
    {
        $collection = new ArrayResourceCollection(array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestDirectory('/webmozart/puli/config', array(
                        new TestFile('/webmozart/puli/config/config.yml'),
                        new TestFile('/webmozart/puli/config/routing.yml'),
                    )),
                    new TestDirectory('/webmozart/puli/css', array(
                        new TestFile('/webmozart/puli/css/style.css'),
                    )),
                    new TestFile('/webmozart/puli/installer.json'),
                ))
            )),
        ));

        $recursiveIterator = new RecursiveResourceIteratorIterator(
            new ResourceCollectionIterator(
                $collection,
                ResourceCollectionIterator::CURRENT_AS_NAME
            ),
            RecursiveResourceIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => 'webmozart',
            '/webmozart/puli' => 'puli',
            '/webmozart/puli/config' => 'config',
            '/webmozart/puli/config/config.yml' => 'config.yml',
            '/webmozart/puli/config/routing.yml' => 'routing.yml',
            '/webmozart/puli/css' => 'css',
            '/webmozart/puli/css/style.css' => 'style.css',
            '/webmozart/puli/installer.json' => 'installer.json',
        );

        $this->assertSame($expected, iterator_to_array($recursiveIterator));
    }

    public function testKeyAsPath()
    {
        $collection = new ArrayResourceCollection(array(
            new TestDirectory('/webmozart/puli/config'),
            new TestDirectory('/webmozart/puli/css'),
            new TestDirectory('/webmozart/puli/images'),
            new TestDirectory('/webmozart/puli/installer.json'),
        ));

        $iterator = new ResourceCollectionIterator(
            $collection,
            ResourceCollectionIterator::CURRENT_AS_PATH
                | ResourceCollectionIterator::KEY_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/config' => '/webmozart/puli/config',
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/images' => '/webmozart/puli/images',
            '/webmozart/puli/installer.json' => '/webmozart/puli/installer.json',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }
}
