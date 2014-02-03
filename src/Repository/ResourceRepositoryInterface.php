<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Repository;

use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\PatternLocator\PatternLocatorInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceRepositoryInterface extends ResourceLocatorInterface
{
    public function add($selector, $realPath);

    public function remove($selector);

    public function tag($selector, $tag);

    public function untag($selector, $tag = null);

    public function addPatternLocator(PatternLocatorInterface $patternLocator);

    public function setDefaultPatternClass($class);
}
