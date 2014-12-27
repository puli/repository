<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\OverriddenPathLoader;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResourceLocalTest extends AbstractLocalResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        parent::setUp();

        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    protected function createLocalResource($localPath, $path = null, $version = 1)
    {
        return new LocalDirectoryResource($localPath, $path, $version);
    }

    protected function getValidLocalPath()
    {
        return $this->fixturesDir.'/dir1';
    }

    protected function getValidLocalPath2()
    {
        return $this->fixturesDir.'/dir2';
    }

    protected function getValidLocalPath3()
    {
        return $this->fixturesDir.'/empty';
    }

    public function getInvalidLocalPaths()
    {
        // setUp() has not yet been called in the data provider
        $fixturesDir = realpath(__DIR__.'/Fixtures');

        return array(
            // No directory
            array($fixturesDir.'/file3'),
            // Does not exist
            array($fixturesDir.'/foobar'),
        );
    }
}
