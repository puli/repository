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

use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\PatternLocator\GlobPatternLocator;
use Webmozart\Puli\Repository\NoDirectoryException;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Util\Path;

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
        $this->patternFactory = $patternFactory ?: new GlobPatternLocator();
    }

    /**
     * {@inheritdoc}
     */
    public function get($selector)
    {
        if (is_string($selector) && $this->patternFactory->acceptsSelector($selector)) {
            $selector = $this->patternFactory->createPattern($selector);
        }

        if ($selector instanceof PatternInterface) {
            return $this->getPatternImpl($selector);
        }

        if (is_array($selector)) {
            $resources = array();

            foreach ($selector as $path) {
                $result = $this->get($path);
                $result = is_array($result) ? $result : array($result);

                foreach ($result as $resource) {
                    $resources[] = $resource;
                }
            }

            return $resources;
        }

        return $this->getImpl(Path::canonicalize($selector));
    }

    public function listDirectory($repositoryPath)
    {
        $resource = $this->getImpl(Path::canonicalize($repositoryPath));

        if ($resource instanceof DirectoryResourceInterface) {
            return $resource->all();
        }

        throw new NoDirectoryException(sprintf(
            'The resource "%s" is not a directory, but a file.',
            $repositoryPath
        ));
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

        if (is_array($selector)) {
            foreach ($selector as $path) {
                if (!$this->contains($path)) {
                    return false;
                }
            }

            return true;
        }

        return $this->containsImpl(Path::canonicalize($selector));
    }

    abstract protected function getImpl($repositoryPath);

    abstract protected function getPatternImpl(PatternInterface $pattern);

    abstract protected function containsImpl($repositoryPath);

    abstract protected function containsPatternImpl(PatternInterface $pattern);
}
