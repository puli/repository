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

use Webmozart\Puli\Pattern\PatternInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface PatternLocatorInterface
{
    public function accepts(PatternInterface $pattern);

    public function locatePaths(PatternInterface $pattern);
}
