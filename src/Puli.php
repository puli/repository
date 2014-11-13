<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli;

/**
 * Contains metadata of the Puli library.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Puli
{
    const VERSION = '@package_version@';

    const RELEASE_DATE = '@release_date@';

    private function __construct()
    {
    }
}
