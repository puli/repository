<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource\Iterator;

use Webmozart\Puli\Resource\Collection\ResourceCollection;
use Webmozart\Puli\Resource\Iterator\RecursiveResourceIterator;
use Webmozart\Puli\Resource\Iterator\ResourceCollectionIterator;
use Webmozart\Puli\Resource\Iterator\ResourceFilterIterator;
use Webmozart\Puli\Tests\Resource\TestDirectory;
use Webmozart\Puli\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceFilterIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceCollection
     */
    private $collection;

    protected function setUp()
    {
        $this->collection = new ResourceCollection(array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestDirectory('/webmozart/puli/config', array(
                        new TestFile('/webmozart/puli/config/config.yml'),
                        new TestFile('/webmozart/puli/config/routing.yml'),
                    )),
                    new TestDirectory('/webmozart/puli/css', array(
                        new TestDirectory('/webmozart/puli/css/bootstrap', array(
                            new TestFile('/webmozart/puli/css/bootstrap/bootstrap.css'),
                        )),
                        new TestFile('/webmozart/puli/css/fonts.css'),
                        new TestFile('/webmozart/puli/css/style.css'),
                    )),
                    new TestFile('/webmozart/puli/installer.json'),
                ))
            )),
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRejectEmptyPattern()
    {
        $innerIterator = new ResourceCollectionIterator(new ResourceCollection());

        new ResourceFilterIterator($innerIterator, '');
    }

    public function testFilterPathPrefix()
    {
        $iterator = new ResourceFilterIterator(
            new RecursiveResourceIterator(
                new ResourceCollectionIterator(
                    $this->collection,
                    ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_NAME
                ),
                RecursiveResourceIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
        );

        $expected = array(
            '/webmozart/puli/css' => 'css',
            '/webmozart/puli/css/bootstrap' => 'bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
            '/webmozart/puli/css/fonts.css' => 'fonts.css',
            '/webmozart/puli/css/style.css' => 'style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathSuffix()
    {
        $iterator = new ResourceFilterIterator(
            new RecursiveResourceIterator(
                new ResourceCollectionIterator(
                    $this->collection,
                    ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_NAME
                ),
                RecursiveResourceIterator::SELF_FIRST
            ),
            '.css',
            ResourceFilterIterator::MATCH_SUFFIX
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
            '/webmozart/puli/css/fonts.css' => 'fonts.css',
            '/webmozart/puli/css/style.css' => 'style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathRegexImplicit()
    {
        $iterator = new ResourceFilterIterator(
            new RecursiveResourceIterator(
                new ResourceCollectionIterator(
                    $this->collection,
                    ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_NAME
                ),
                RecursiveResourceIterator::SELF_FIRST
            ),
            '/\.css$/'
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
            '/webmozart/puli/css/fonts.css' => 'fonts.css',
            '/webmozart/puli/css/style.css' => 'style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathRegexExplicit()
    {
        $iterator = new ResourceFilterIterator(
            new RecursiveResourceIterator(
                new ResourceCollectionIterator(
                    $this->collection,
                    ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_NAME
                ),
                RecursiveResourceIterator::SELF_FIRST
            ),
            '/\.css$/',
            ResourceFilterIterator::MATCH_REGEX
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
            '/webmozart/puli/css/fonts.css' => 'fonts.css',
            '/webmozart/puli/css/style.css' => 'style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterNamePrefix()
    {
        $iterator = new ResourceFilterIterator(
            new RecursiveResourceIterator(
                new ResourceCollectionIterator(
                    $this->collection,
                    ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_NAME
                ),
                RecursiveResourceIterator::SELF_FIRST
            ),
            'bootstrap',
            ResourceFilterIterator::FILTER_BY_NAME | ResourceFilterIterator::MATCH_PREFIX
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap' => 'bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }
}
