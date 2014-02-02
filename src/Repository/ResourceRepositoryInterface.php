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
    public function add($repositoryPath, $realPath);

    /**
     * @param string $repositoryPath
     *
     * @return boolean
     */
    public function contains($repositoryPath);

    public function remove($repositoryPath);

    public function tag($repositoryPath, $tag);

    public function untag($repositoryPath, $tag = null);

    public function getTags($repositoryPath = null);

    public function getPaths($repositoryPath);
}
