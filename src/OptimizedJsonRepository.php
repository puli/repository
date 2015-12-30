<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use ArrayIterator;
use Iterator;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\LinkResource;
use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobFilterIterator;
use Webmozart\Glob\Iterator\RegexFilterIterator;
use Webmozart\PathUtil\Path;

/**
 * An optimized path mapping resource repository.
 * When a resource is added, all its children are resolved
 * and getting them is much faster.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\OptimizedJsonRepository;
 *
 * $repo = new OptimizedJsonRepository();
 * $repo->add('/css', new DirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * This repository only supports instances of FilesystemResource.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedJsonRepository extends AbstractJsonRepository implements EditableRepository
{
    /**
     * {@inheritdoc}
     */
    protected function insertReference($path, $reference)
    {
        $this->json[$path] = $reference;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeReferences($glob)
    {
        $removed = 0;

        foreach ($this->getReferencesForGlob($glob.'{,/**/*}') as $path => $reference) {
            ++$removed;

            unset($this->json[$path]);
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferencesForPath($path)
    {
        if (!array_key_exists($path, $this->json)) {
            return array();
        }

        $reference = $this->json[$path];

        // We're only interested in the first entry of eventual arrays
        if (is_array($reference)) {
            $reference = reset($reference);
        }

        if ($this->isFilesystemReference($reference)) {
            $reference = Path::makeAbsolute($reference, $this->baseDirectory);

            // Ignore non-existing files. Not sure this is the right
            // thing to do.
            if (!file_exists($reference)) {
                return array();
            }
        }

        return array($path => $reference);
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferencesForGlob($glob, $stopOnFirst = false)
    {
        if (!Glob::isDynamic($glob)) {
            return $this->getReferencesForPath($glob);
        }

        return $this->getReferencesForRegex(
            Glob::getStaticPrefix($glob),
            Glob::toRegEx($glob),
            $stopOnFirst
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferencesForRegex($staticPrefix, $regex, $stopOnFirst = false)
    {
        $result = array();
        $foundMappingsWithPrefix = false;

        foreach ($this->json as $path => $reference) {
            if (0 === strpos($path, $staticPrefix)) {
                $foundMappingsWithPrefix = true;

                if (preg_match($regex, $path)) {
                    // We're only interested in the first entry of eventual arrays
                    if (is_array($reference)) {
                        $reference = reset($reference);
                    }

                    if ($this->isFilesystemReference($reference)) {
                        $reference = Path::makeAbsolute($reference, $this->baseDirectory);

                        // Ignore non-existing files. Not sure this is the right
                        // thing to do.
                        if (!file_exists($reference)) {
                            continue;
                        }
                    }

                    $result[$path] = $reference;

                    if ($stopOnFirst) {
                        return $result;
                    }
                }

                continue;
            }

            // We did not find anything but previously found mappings with the
            // static prefix
            // The mappings are sorted alphabetically, so we can safely abort
            if ($foundMappingsWithPrefix) {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferencesInDirectory($path, $stopOnFirst = false)
    {
        $basePath = rtrim($path, '/');

        return $this->getReferencesForRegex(
            $basePath.'/',
            '~^'.preg_quote($basePath, '~').'/[^/]+$~',
            $stopOnFirst
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function addFilesystemResource($path, FilesystemResource $resource)
    {
        // Read children before attaching the resource to this repository
        $children = $resource->listChildren();

        parent::addFilesystemResource($path, $resource);

        // Recursively add all child resources
        $basePath = '/' === $path ? $path : $path.'/';

        foreach ($children as $name => $child) {
            $this->addFilesystemResource($basePath.$name, $child);
        }
    }
}
