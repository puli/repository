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

use Webmozart\Puli\Locator\PhpCacheLocator;
use Webmozart\Puli\LocatorDumper\PhpCacheDumper;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpCacheLocatorTest extends AbstractResourceLocatorTest
{
    private static $tempDir;

    /**
     * @var PhpCacheDumper
     */
    private $dumper;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$tempDir = sys_get_temp_dir().'/puli/PhpCacheLocatorTest';
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (file_exists(self::$tempDir)) {
            self::$filesystem->remove(self::$tempDir);
        }
    }

    protected function setUp()
    {
        parent::setUp();

        $this->dumper = new PhpCacheDumper();

        self::$filesystem->remove(self::$tempDir);
        mkdir(self::$tempDir, 0777, true);
    }

    protected function dumpLocator()
    {
        $this->dumper->dumpLocator($this->repo, self::$tempDir);

        $this->locator = new PhpCacheLocator(self::$tempDir);
    }
}
