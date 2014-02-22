<?php

/*
 * This file is part of the Symfony Puli bridge.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Symfony\HttpKernel;

use Symfony\Component\HttpKernel\Config\FileLocator;
use Webmozart\Puli\Extension\Symfony\Config\ChainableFileLocatorInterface;

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
