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

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceRepositoryInterface extends ResourceLocatorInterface
{
    public function add($selector, $realPath);

    /**
     * @param string $selector
     *
     * @return boolean
     */
    public function contains($selector);

    public function remove($selector);

    public function tag($selector, $tag);

    public function untag($selector, $tag = null);

    public function getTags($selector = null);

    public function getPaths($selector);
}
