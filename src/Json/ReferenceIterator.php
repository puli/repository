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

use ArrayIterator;
use Webmozart\PathUtil\Path;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ReferenceIterator extends ArrayIterator
{
    private $path;

    private $baseDirectory;

    public function __construct(array &$json, $path, $baseDirectory)
    {
        $reference = $json[$path];

        parent::__construct(is_array($reference) ? $reference : array($reference));

        $this->path = $path;
        $this->baseDirectory = $baseDirectory;
    }

    public function key()
    {
        // The keys are the Puli paths, which are all the same in this case
        // Don't pass this iterator to iterator_to_array()!
        return $this->path;
    }

    public function current()
    {
        $current = parent::current();

        if (null === $current) {
            return null;
        }

        if (isset($current{0}) && '@' === $current{0}) {
            // Link
            return $current;
        }

        $filesystemPath = Path::makeAbsolute($current, $this->baseDirectory);

        if (!file_exists($filesystemPath)) {
            // Houston we got a problem
        }

        return $filesystemPath;
    }
}
