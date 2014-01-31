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
    public function addResource($repositoryPath, $realPath);

    public function addResources($repositoryPath, $pattern);

    /**
     * @param string $repositoryPath
     *
     * @return boolean
     */
    public function containsResource($repositoryPath);

    public function containsResources($pattern);

    public function removeResource($repositoryPath);

    public function removeResources($pattern);

    public function tagResource($repositoryPath, $tag);

    public function tagResources($pattern, $tag);

    public function untagResource($repositoryPath, $tag = null);

    public function untagResources($pattern, $tag);

    public function getTags($repositoryPath = null);

    public function getPaths($repositoryPath);
}
