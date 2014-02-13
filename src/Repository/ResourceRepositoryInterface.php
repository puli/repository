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
use Webmozart\Puli\Pattern\PatternInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceRepositoryInterface extends ResourceLocatorInterface
{
    /**
     * @param string $selector
     * @param string|PatternInterface $realPath
     */
    public function add($selector, $realPath);

    /**
     * @param string|PatternInterface $selector
     */
    public function remove($selector);

    /**
     * @param string|PatternInterface $selector
     * @param string $tag
     */
    public function tag($selector, $tag);

    /**
     * @param string|PatternInterface $selector
     * @param string|null $tag
     */
    public function untag($selector, $tag = null);
}
