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

use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliTemplateLoader implements \Twig_LoaderInterface
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
            // The "loaded_by_puli" tag makes it possible to recognize node
            // trees of templates loaded through this loader. In this way, we
            // can turn relative Puli paths into absolute ones in those
            // templates. The "loaded_by_puli" tag is removed early on by the
            // LoadedByPuliTagger visitor and does not appear in the final
            // output.
            return "{% loaded_by_puli %}\n".file_get_contents($this->locator->get($path)->getRealPath());
        } catch (ResourceNotFoundException $e) {
            throw new \Twig_Error_Loader($e->getMessage(), -1, null, $e);
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
            // The result of this method MUST NOT be the real path (without
            // prefix), because then the generated file has the same cache
            // key as the same template loaded through a different loader.
            // If loaded through a different loader, relative paths won't be
            // resolved, so we'll have the wrong version of the template in
            // he cache.
            return '__puli__'.$this->locator->get($path)->getPath();
        } catch (ResourceNotFoundException $e) {
            throw new \Twig_Error_Loader($e->getMessage(), -1, null, $e);
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
