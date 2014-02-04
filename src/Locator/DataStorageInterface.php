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
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface DataStorageInterface
{
    public function getAlternativePaths($repositoryPath);

    public function getTags($repositoryPath);

    /**
     * @param $repositoryPath
     *
     * @return \Webmozart\Puli\Resource\ResourceInterface[]
     */
    public function getDirectoryEntries($repositoryPath);
}
