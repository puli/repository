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

use Iterator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilterReferencesIterator implements Iterator
{
    private $json;

    private $searchPath;

    private $baseDirectory;

    private $currentPath;

    private $currentReference;

    /**
     * @var Iterator
     */
    private $currentIterator;

    private $foundMatchingMappings = false;

    private $done;

    public function __construct(array &$json, $searchPath, $baseDirectory)
    {
        $this->json = &$json;
        $this->searchPath = rtrim($searchPath, '/').'/';
        $this->baseDirectory = $baseDirectory;
    }

    public function current()
    {
        return $this->currentReference;
    }

    public function next()
    {
        if (null === $this->currentIterator) {
            return;
        }

        $this->currentIterator->next();

        if ($this->currentIterator->valid()) {
            $this->currentPath = $this->currentIterator->key();
            $this->currentReference = $this->currentIterator->current();

            return;
        }

        prev($this->json);

        while (!$this->accept() && !$this->done) {
            prev($this->json);
        }
    }

    public function key()
    {
        return $this->currentPath;
    }

    public function valid()
    {
        return null !== $this->currentPath;
    }

    public function rewind()
    {
        $this->done = false;

        end($this->json);

        while (!$this->accept() && !$this->done) {
            prev($this->json);
        }
    }

    private function accept()
    {
        $this->currentPath = null;
        $this->currentReference = null;
        $this->currentIterator = null;

        // End of outer iterator
        if (null === $currentPath = key($this->json)) {
            $this->done = true;

            return false;
        }

        $currentPathForTest = rtrim($currentPath, '/').'/';

        // We found a mapping that matches or lies within the search path
        // e.g. mapping /a/b/c for path /a/b
        // e.g. mapping /a/b for path /a/b
        if (0 === strpos($currentPathForTest, $this->searchPath)) {
            $this->foundMatchingMappings = true;
            $this->currentIterator = new ReferenceIterator($this->json, $currentPath, $this->baseDirectory);
            $this->currentIterator->rewind();

            if (!$this->currentIterator->valid()) {
                return false;
            }

            $this->currentPath = $this->currentIterator->key();
            $this->currentReference = $this->currentIterator->current();

            return true;
        }

        // We found a mapping that is an ancestor of the search path
        // e.g. mapping /a for path /a/b
        if (0 === strpos($this->searchPath, $currentPathForTest)) {
            $this->foundMatchingMappings = true;
            $this->currentIterator = new NestedPathIterator(
                new ReferenceIterator($this->json, $currentPath, $this->baseDirectory),
                substr($this->searchPath, strlen($currentPathForTest), -1)
            );
            $this->currentIterator->rewind();

            if (!$this->currentIterator->valid()) {
                return false;
            }

            $this->currentPath = $this->currentIterator->key();
            $this->currentReference = $this->currentIterator->current();

            return true;
        }

        // We did not find anything but previously found mappings
        // The mappings are sorted alphabetically, so we can safely abort
        if ($this->foundMatchingMappings) {
            $this->done = true;
        }

        return false;
    }
}
