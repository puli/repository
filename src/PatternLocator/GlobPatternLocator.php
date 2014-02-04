<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\PatternLocator;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobPatternLocator implements PatternLocatorInterface, PatternFactoryInterface
{
    public function acceptsSelector($selector)
    {
        return false !== strpos($selector, '*');
    }

    public function createPattern($selector)
    {
        return new GlobPattern($selector);
    }

    public function createPatternLocator()
    {
        return $this;
    }

    public function locatePaths(PatternInterface $pattern)
    {
        return glob((string) $pattern);
    }
}
