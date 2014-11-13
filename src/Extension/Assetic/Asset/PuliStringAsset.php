<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Assetic\Asset;

use Assetic\Asset\StringAsset;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliStringAsset extends StringAsset implements PuliAssetInterface
{
    public function __construct($path, $content, $filters = array())
    {
        parent::__construct($content, $filters, '/', $path);
    }
}
