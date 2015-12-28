<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Json;

use FilterIterator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscardDuplicateKeysIterator extends FilterIterator
{
    private $keys = array();

    public function accept()
    {
        $key = $this->key();

        if (!isset($this->keys[$key])) {
            $this->keys[$key] = true;

            return true;
        }

        return false;
    }
}
