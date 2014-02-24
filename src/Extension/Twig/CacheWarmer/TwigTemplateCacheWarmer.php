<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Twig\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Twig_Environment;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Resource\DirectoryResourceIterator;
use Webmozart\Puli\Resource\ResourceFilterIterator;

/**
 * Generates the Twig cache for all templates in the resource repository.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TwigTemplateCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var ResourceLocatorInterface
     */
    private $locator;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var Twig_Environment
     */
    private $twig;

    public function __construct(ResourceLocatorInterface $locator, \Twig_Environment $twig, $suffix = '.twig')
    {
        $this->locator = $locator;
        $this->suffix = $suffix;
        $this->twig = $twig;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     *
     * @throws \RuntimeException If setEnvironment() wasn't called
     */
    public function warmUp($cacheDir)
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($this->locator->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            $this->suffix,
            ResourceFilterIterator::CURRENT_AS_PATH
                | ResourceFilterIterator::FILTER_BY_NAME
                | ResourceFilterIterator::MATCH_SUFFIX
        );

        foreach ($iterator as $path) {
            try {
                $this->twig->loadTemplate($path);
            } catch (\Twig_Error $e) {
                // Problem during compilation, stop
            }
        }
    }

    /**
     * Returns whether this warmer is optional or not.
     *
     * @return Boolean always true
     */
    public function isOptional()
    {
        return true;
    }
}
