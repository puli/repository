<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem\Iterator;

use Puli\Repository\Filesystem\Iterator\SortingIterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SortingIteratorTest extends \PHPUnit_Framework_TestCase
{
    private static $array = array(
        'a' => 'b',
        'b' => array(
            'b2' => 'bb',
            'b3' => 'ba',
            'b1' => 'bc',
        ),
        'd' => 'c',
        'c' => 'a',
    );

    public function testSortValuesByDefault()
    {
        $iterator = new SortingIterator(
            new \ArrayIterator(self::$array),
            0
        );

        $this->assertSame(array(
            'c' => 'a',
            'a' => 'b',
            'd' => 'c',
            'b' => array(
                'b2' => 'bb',
                'b3' => 'ba',
                'b1' => 'bc',
            ),
        ), iterator_to_array($iterator));
    }

    public function testSortValues()
    {
        $iterator = new SortingIterator(
            new \ArrayIterator(self::$array),
            SortingIterator::SORT_VALUE
        );

        $this->assertSame(array(
            'c' => 'a',
            'a' => 'b',
            'd' => 'c',
            'b' => array(
                'b2' => 'bb',
                'b3' => 'ba',
                'b1' => 'bc',
            ),
        ), iterator_to_array($iterator));
    }

    public function testRecursiveSortValues()
    {
        $iterator = new \RecursiveIteratorIterator(
            new SortingIterator(
                new \RecursiveArrayIterator(self::$array),
                SortingIterator::SORT_VALUE
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $this->assertSame(array(
            'c' => 'a',
            'a' => 'b',
            'd' => 'c',
            'b3' => 'ba',
            'b2' => 'bb',
            'b1' => 'bc',
        ), iterator_to_array($iterator));
    }

    public function testSortKeys()
    {
        $iterator = new SortingIterator(
            new \ArrayIterator(self::$array),
            SortingIterator::SORT_KEY
        );

        $this->assertSame(array(
            'a' => 'b',
            'b' => array(
                'b2' => 'bb',
                'b3' => 'ba',
                'b1' => 'bc',
            ),
            'c' => 'a',
            'd' => 'c',
        ), iterator_to_array($iterator));
    }

    public function testRecursiveSortKeys()
    {
        $iterator = new \RecursiveIteratorIterator(
            new SortingIterator(
                new \RecursiveArrayIterator(self::$array),
                SortingIterator::SORT_KEY
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $this->assertSame(array(
            'a' => 'b',
            'b1' => 'bc',
            'b2' => 'bb',
            'b3' => 'ba',
            'c' => 'a',
            'd' => 'c',
        ), iterator_to_array($iterator));
    }
}
