<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceInterface
{
    public function __toString();

    public function getRepositoryPath();

    public function getName();

    public function getPath();

    /**
     * @param string $path
     *
     * @internal You should use {@link \Webmozart\Puli\Repository\ResourceRepositoryInterface::add()}.
     */
    public function overridePath($path);

    public function getAlternativePaths();

    /**
     * @internal You should use {@link \Webmozart\Puli\Repository\ResourceRepositoryInterface::tag()}.
     */
    public function addTag($tag);

    /**
     * @internal You should use {@link \Webmozart\Puli\Repository\ResourceRepositoryInterface::untag()}.
     */
    public function removeTag($tag);

    /**
     * @return string[]
     */
    public function getTags();
}
