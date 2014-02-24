<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Twig;

use Twig_Error_Loader;
use Twig_LoaderInterface;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliLoader implements Twig_LoaderInterface
{
    private $locator;

    public function __construct(ResourceLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $path The name of the template to load
     *
     * @return string The template source code
     *
     * @throws \Twig_Error_Loader When $path is not found
     */
    public function getSource($path)
    {
        try {
            // The "resolve_puli_paths" tag tells the RelativePathResolver that
            // it should turn relative paths into absolute paths in this file.
            // That tag is later removed from the node tree and has no effect
            // on the rendered output of the file.
            return "{% resolve_puli_paths %}\n".file_get_contents($this->locator->get($path)->getRealPath());
        } catch (ResourceNotFoundException $e) {
            throw new Twig_Error_Loader($e->getMessage(), -1, null, $e);
        }
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $path The name of the template to load
     *
     * @return string The cache key
     *
     * @throws \Twig_Error_Loader When $path is not found
     */
    public function getCacheKey($path)
    {
        try {
            // Even thow the path and $path are the same, call the locator to
            // make sure that the path actually exists
            return '__puli__'.$this->locator->get($path)->getPath();
        } catch (ResourceNotFoundException $e) {
            throw new Twig_Error_Loader($e->getMessage(), -1, null, $e);
        }
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string    $path The template name
     * @param timestamp $time The last modification time of the cached template
     *
     * @return Boolean true if the template is fresh, false otherwise
     *
     * @throws \Twig_Error_Loader When $path is not found
     */
    public function isFresh($path, $time)
    {
        try {
            return filemtime($this->locator->get($path)->getRealPath()) <= $time;
        } catch (ResourceNotFoundException $e) {
            throw new \Twig_Error_Loader($e->getMessage(), -1, null, $e);
        }
    }
}
