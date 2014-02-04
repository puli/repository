<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Locator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceLocatorInterface
{
    /**
     * @param string $selector
     *
     * @return \Webmozart\Puli\Resource\ResourceInterface
     */
    public function get($selector);

    /**
     * @param string $selector
     *
     * @return boolean
     */
    public function contains($selector);

    public function getByTag($tag);

    public function listDirectory($repositoryPath);

    /**
     * @return \Webmozart\Puli\Tag\TagInterface[]
     */
    public function getTags();
}
