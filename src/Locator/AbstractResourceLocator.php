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

use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Pattern\GlobPatternFactory;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\FileResourceInterface;
use Webmozart\Puli\Resource\NoDirectoryException;
use Webmozart\Puli\Resource\ResourceCollectionInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResourceLocator implements ResourceLocatorInterface
{
    /**
     * @var PatternFactoryInterface
     */
    protected $patternFactory;

    public function __construct(PatternFactoryInterface $patternFactory = null)
    {
        $this->patternFactory = $patternFactory ?: new GlobPatternFactory();
    }

    public function get($path)
    {
        return $this->getImpl(Path::canonicalize($path));
    }

    public function find($selector)
    {
        if (is_string($selector)) {
            $selector = $this->patternFactory->createPattern($selector);
        }

        if (!$selector instanceof PatternInterface) {
            throw new \Exception();
        }

        return $this->findImpl($selector);
    }

    public function listDirectory($path)
    {
        $resource = $this->getImpl(Path::canonicalize($path));

        if ($resource instanceof DirectoryResourceInterface) {
            return $resource->listEntries();
        }

        throw new NoDirectoryException($path);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        if (is_string($selector) && $this->patternFactory->acceptsSelector($selector)) {
            $selector = $this->patternFactory->createPattern($selector);
        }

        if ($selector instanceof PatternInterface) {
            return $this->containsPatternImpl($selector);
        }

        return $this->containsImpl(Path::canonicalize($selector));
    }

    /**
     * @param string $path
     *
     * @return FileResourceInterface|DirectoryResourceInterface
     */
    abstract protected function getImpl($path);

    /**
     * @param PatternInterface $pattern
     *
     * @return ResourceCollectionInterface|FileResourceInterface[]|DirectoryResourceInterface[]
     */
    abstract protected function findImpl(PatternInterface $pattern);

    /**
     * @param $path
     *
     * @return boolean
     */
    abstract protected function containsImpl($path);

    /**
     * @param PatternInterface $pattern
     *
     * @return boolean
     */
    abstract protected function containsPatternImpl(PatternInterface $pattern);
}
