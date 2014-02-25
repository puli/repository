<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Assetic\Twig;

use Webmozart\Puli\Extension\Assetic\Twig\NodeVisitor\AssetPathResolver;
use Webmozart\Puli\Locator\ResourceLocatorInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAsseticExtension extends \Twig_Extension
{
    /**
     * @var ResourceLocatorInterface
     */
    private $locator;

    public function __construct(ResourceLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'puli-assetic';
    }

    public function getNodeVisitors()
    {
        return array(new AssetPathResolver($this->locator));
    }
}
