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

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Webmozart\Glob\Glob;
use Webmozart\PathUtil\Path;

/**
 * A repository backed by a JSON file optimized for reading.
 *
 * The generated JSON file is described by res/schema/repository-schema-1.0.json.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\OptimizedJsonRepository;
 *
 * $repo = new OptimizedJsonRepository('/path/to/repository.json', '/path/to/project');
 * $repo->add('/css', new DirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * When adding a resource, the added filesystem path is stored in the JSON file
 * under the key of the Puli path. The path is stored relatively to the base
 * directory passed to the constructor. Directories will be expanded and all
 * nested files will be added to the mapping file as well:
 *
 * ```json
 * {
 *     "/css": "res/css",
 *     "/css/style.css": "res/css/style.css"
 * }
 * ```
 *
 * Mapped resources can be read with the method {@link get()}:
 *
 * ```php
 * $cssPath = $repo->get('/css')->getFilesystemPath();
 * ```
 *
 * You can also access nested files:
 *
 * ```php
 * echo $repo->get('/css/style.css')->getBody();
 * ```
 *
 * Since nested files are searched during {@link add()} and added to the JSON
 * file, this repository does not detect any files that you add to a directory
 * after adding that directory to the repository. This means that accessing
 * files is very fast, but also that the usage of this repository implementation
 * can be cumbersome in development environments. There you are recommended to
 * use {@link JsonRepository} instead.
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
    public function clear()
    {
        if (null === $this->json) {
            $this->load();
        }

        // Subtract root which is not deleted
        $removed = count($this->json) - 1;

        $this->json = array();

        $this->flush();

        $this->clearVersions();
        $this->storeVersion($this->get('/'));

        return $removed;
    }

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

            $this->removeVersions($path);

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
            $absoluteReference = Path::makeAbsolute($reference, $this->baseDirectory);

            if (!file_exists($absoluteReference)) {
                $this->logReferenceNotFound($path, $reference, $absoluteReference);

                return array();
            }

            $reference = $absoluteReference;
        }

        return array($path => $reference);
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferencesForGlob($glob, $flags = 0)
    {
        if (!Glob::isDynamic($glob)) {
            return $this->getReferencesForPath($glob);
        }

        return $this->getReferencesForRegex(
            Glob::getStaticPrefix($glob),
            Glob::toRegEx($glob),
            $flags
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferencesForRegex($staticPrefix, $regex, $flags = 0)
    {
        $result = array();
        $foundMappingsWithPrefix = false;

        foreach ($this->json as $path => $reference) {
            if (0 === strpos($path, $staticPrefix)) {
                $foundMappingsWithPrefix = true;

                if (!preg_match($regex, $path)) {
                    continue;
                }

                // We're only interested in the first entry of eventual arrays
                if (is_array($reference)) {
                    $reference = reset($reference);
                }

                if ($this->isFilesystemReference($reference)) {
                    $absoluteReference = Path::makeAbsolute($reference, $this->baseDirectory);

                    if (!file_exists($absoluteReference)) {
                        $this->logReferenceNotFound($path, $reference, $absoluteReference);

                        continue;
                    }

                    $reference = $absoluteReference;
                }

                $result[$path] = $reference;

                if ($flags & self::STOP_ON_FIRST) {
                    return $result;
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
    protected function getReferencesInDirectory($path, $flags = 0)
    {
        $basePath = rtrim($path, '/');

        return $this->getReferencesForRegex(
            $basePath.'/',
            '~^'.preg_quote($basePath, '~').'/[^/]+$~',
            $flags
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
