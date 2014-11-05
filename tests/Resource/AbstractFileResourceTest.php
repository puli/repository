<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource;

use Webmozart\Puli\Resource\FileResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractFileResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return FileResourceInterface
     */
    abstract protected function createFile();

    public function testCopy()
    {
        $file = $this->createFile();

        $copy = $file->copyTo('/new/path');

        $this->assertNotSame($copy, $file);
        $this->assertSame('/new/path', $copy->getPath());
        $this->assertSame('path', $copy->getName());
    }

    public function testOverride()
    {
        $file = $this->createFile()->copyTo('/webmozart/puli/file');
        $overridden = $this->createFile()->copyTo('/other/path');

        $override = $file->override($overridden);

        $this->assertNotSame($override, $file);
        $this->assertNotSame($override, $overridden);
        $this->assertSame('/other/path', $override->getPath());
        $this->assertSame('path', $override->getName());
    }
}
