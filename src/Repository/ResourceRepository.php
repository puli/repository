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

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository implements ResourceRepositoryInterface
{
    private $paths = array();

    /**
     * @var \Webmozart\Puli\Resource\ResourceInterface[]
     */
    private $resources = array();

    /**
     * {@inheritdoc}
     */
    public function getResource($repositoryPath)
    {
        if (!isset($this->resources[$repositoryPath])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $repositoryPath
            ));
        }

        return $this->resources[$repositoryPath];
    }

    public function getResources($pattern)
    {

    }

    public function getTaggedResources($tag)
    {

    }

    public function listDirectory($repositoryPath)
    {

    }

    public function addResource($repositoryPath, $realPath)
    {
        $isDirectory = is_dir($realPath);

        // Recursively add directory contents
        if ($isDirectory) {
            $iterator = new \FilesystemIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);

            foreach ($iterator as $path) {
                $this->addResource($repositoryPath.'/'.basename($path), $path);
            }
        }

        // Create new Resource instances if necessary
        if (!isset($this->paths[$repositoryPath])) {
            $this->paths[$repositoryPath] = array($realPath);

            $this->resources[$repositoryPath] = $isDirectory
                ? new DirectoryResource(
                    $repositoryPath,
                    array($realPath)
                )
                : new FileResource(
                    $repositoryPath,
                    $realPath
                );

            return;
        }

        $this->paths[$repositoryPath][] = $realPath;
        $this->resources[$repositoryPath]->refresh($this);
    }

    public function addResources($repositoryPath, $pattern)
    {
        if (is_string($pattern)) {
            $resources = glob($pattern);
        } elseif (is_array($pattern)) {
            $resources = $pattern;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The argument $pattern should be a string or an array, but is: %s.',
                gettype($pattern)
            ));
        }

        foreach ($resources as $resource) {
            $this->addResource($repositoryPath.'/'.basename($resource), $resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function containsResource($repositoryPath)
    {
        return isset($this->resources[$repositoryPath]);
    }

    public function containsResources($pattern)
    {
        if (is_string($pattern)) {
            $pattern = new GlobPattern($pattern);
        }

        if ($pattern instanceof PatternInterface) {
            $staticPrefix = $pattern->getStaticPrefix();
            $regExp = $pattern->getRegularExpression();

            foreach ($this->resources as $repositoryPath => $resource) {
                if (0 !== strpos($repositoryPath, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $repositoryPath)) {
                    continue;
                }

                return true;
            }

            return false;
        }
    }

    public function removeResource($repositoryPath)
    {

    }

    public function removeResources($pattern)
    {

    }

    public function tagResource($repositoryPath, $tag)
    {

    }

    public function tagResources($pattern, $tag)
    {

    }

    public function untagResource($repositoryPath, $tag = null)
    {

    }

    public function untagResources($pattern, $tag)
    {

    }

    public function getTags($repositoryPath = null)
    {

    }

    public function getPaths($repositoryPath)
    {
        return $this->paths[$repositoryPath];
    }
}
