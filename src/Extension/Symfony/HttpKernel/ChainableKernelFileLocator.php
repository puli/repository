<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Symfony\HttpKernel;

use Puli\Extension\Symfony\Config\ChainableFileLocatorInterface;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ChainableKernelFileLocator extends FileLocator implements ChainableFileLocatorInterface
{
    public function supports($path)
    {
        return isset($path[0]) && '@' === $path[0];
    }
}
