<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractFilesystemRepositoryTest extends AbstractEditableRepositoryTest
{
    protected function assertPathsAreEqual($expected, $actual)
    {
        $normalize = function ($path) {
            return str_replace(DIRECTORY_SEPARATOR, '/', $path);
        };

        $this->assertEquals($normalize($expected), $normalize($actual));
    }
}
