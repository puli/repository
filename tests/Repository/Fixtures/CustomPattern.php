<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Repository\Fixtures;

use Webmozart\Puli\Pattern\PatternInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CustomPattern implements PatternInterface
{
    public function getStaticPrefix()
    {
    }

    public function getRegularExpression()
    {
    }

    public function __toString()
    {
        return '';
    }
}
