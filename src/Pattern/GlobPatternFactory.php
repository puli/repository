<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Pattern;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobPatternFactory
{
    public function acceptsSelector($selector)
    {
        return false !== strpos($selector, '*');
    }

    public function createPattern($selector)
    {
        return new GlobPattern($selector);
    }
}
