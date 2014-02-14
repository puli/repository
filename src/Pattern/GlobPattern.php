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

use Webmozart\Puli\Path\Path;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobPattern implements PatternInterface
{
    private $pattern;

    private $staticPrefix;

    private $regExp;

    public function __construct($pattern)
    {
        $this->pattern = Path::canonicalize($pattern);
        $this->regExp = '~^'.str_replace('\*', '[^/]+', preg_quote($this->pattern, '~')).'$~';

        if (false !== ($pos = strpos($this->pattern, '*'))) {
            $this->staticPrefix = substr($this->pattern, 0, $pos);
        } else {
            $this->staticPrefix = $this->pattern;
        }
    }

    public function getStaticPrefix()
    {
        return $this->staticPrefix;
    }

    public function getRegularExpression()
    {
        return $this->regExp;
    }

    public function __toString()
    {
        return $this->pattern;
    }
}
