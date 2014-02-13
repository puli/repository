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

use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceLocatorInterface
{
    /**
     * @param string|PatternInterface $selector
     *
     * @return ResourceInterface|ResourceInterface[]
     */
    public function get($selector);

    /**
     * @param string|PatternInterface $selector
     *
     * @return boolean
     */
    public function contains($selector);

    /**
     * @param string $tag
     *
     * @return ResourceInterface[]
     */
    public function getByTag($tag);

    /**
     * @param string $repositoryPath
     *
     * @return ResourceInterface[]
     */
    public function listDirectory($repositoryPath);

    /**
     * @return string[]
     */
    public function getTags();
}
