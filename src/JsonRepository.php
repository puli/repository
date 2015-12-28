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

use BadMethodCallException;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Json\CreateResourcesIterator;
use Puli\Repository\Json\DiscardDuplicateKeysIterator;
use Puli\Repository\Json\FilterMatchesIterator;
use Puli\Repository\Json\FilterPathIterator;
use Puli\Repository\Json\FilterReferencesIterator;
use Puli\Repository\Json\FollowLinksIterator;
use Puli\Repository\Json\ListDirectoriesIterator;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Puli\Repository\Resource\LinkResource;
use RecursiveDirectoryIterator;
use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\PathUtil\Path;

/**
 * A development path mapping resource repository.
 * Each resource is resolved at `get()` time to improve
 * developer experience.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\JsonRepository;
 *
 * $repo = new JsonRepository();
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
class JsonRepository extends AbstractJsonRepository implements EditableRepository
{
    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        if (null === $this->json) {
            $this->load();
        }

        $path = $this->sanitizePath($path);
        $iterator = new CreateResourcesIterator($this->getPathIterator($path), $this);
        $iterator->rewind();

        if ($iterator->valid()) {
            return $iterator->current();
        }

        if ('/' === $path) {
            return new GenericResource('/');
        }

        throw ResourceNotFoundException::forPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        if (null === $this->json) {
            $this->load();
        }

        $this->validateSearchLanguage($language);
        $query = $this->sanitizePath($query);
        $iterator = new CreateResourcesIterator($this->getGlobIterator($query), $this);
        $results = iterator_to_array($iterator);

        ksort($results);

        return new ArrayResourceCollection(array_values($results));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        if (null === $this->json) {
            $this->load();
        }

        $this->validateSearchLanguage($language);
        $query = $this->sanitizePath($query);
        $iterator = $this->getGlobIterator($query);
        $iterator->rewind();

        if ($iterator->valid()) {
            return true;
        }

        return '/' === $query;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        if (null === $this->json) {
            $this->load();
        }

        $this->validateSearchLanguage($language);
        $query = $this->sanitizePath($query);

        Assert::notEmpty(trim($query, '/'), 'The root directory cannot be removed.');

        $checkIterator = $this->getGlobIterator($query);
        $nonDeletablePaths = array();

        foreach ($checkIterator as $path => $filesystemPath) {
            if (!array_key_exists($path, $this->json)) {
                $nonDeletablePaths[] = $filesystemPath;
            }
        }

        if (count($nonDeletablePaths) === 1) {
            throw new BadMethodCallException(sprintf(
                'The remove query "%s" matched a resource that is not a path mapping', $query
            ));
        } elseif (count($nonDeletablePaths) > 1) {
            throw new BadMethodCallException(sprintf(
                'The remove query "%s" matched %s resources that are not path mappings', $query, count($nonDeletablePaths)
            ));
        }

        // Copy to array since we cannot run two iterators at the same time
        $deletedPaths = iterator_to_array($this->getDeleteIterator($query.'{,/**/*}'));
        $removed = 0;

        foreach ($deletedPaths as $path => $filesystemPath) {
            $removed += 1 + iterator_count($this->getGlobIterator($path.'/**/*'));

            unset($this->json[$path]);
        }

        $this->flush();

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (null === $this->json) {
            $this->load();
        }

        // Subtract root which is not deleted
        $removed = iterator_count($this->getGlobIterator('/**/*')) - 1;

        $this->json = array();

        $this->flush();

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        if (null === $this->json) {
            $this->load();
        }

        $path = $this->sanitizePath($path);
        $iterator = new CreateResourcesIterator($this->getChildIterator($path), $this);
        $results = iterator_to_array($iterator);

        if (empty($results)) {
            $checkIterator = $this->getPathIterator($path);
            $checkIterator->rewind();

            if (!$checkIterator->valid()) {
                throw ResourceNotFoundException::forPath($path);
            }
        }

        ksort($results);

        return new ArrayResourceCollection(array_values($results));
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        if (null === $this->json) {
            $this->load();
        }

        $path = $this->sanitizePath($path);
        $iterator = $this->getChildIterator($path);
        $iterator->rewind();

        if (!$iterator->valid()) {
            $checkIterator = $this->getPathIterator($path);
            $checkIterator->rewind();

            if (!$checkIterator->valid()) {
                throw ResourceNotFoundException::forPath($path);
            }

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function addFilesystemResource($path, FilesystemResource $resource)
    {
        $resource->attachTo($this, $path);
        $this->insertReference($path, Path::makeRelative($resource->getFilesystemPath(), $this->baseDirectory));
    }

    /**
     * {@inheritdoc}
     */
    protected function addLinkResource($path, LinkResource $resource)
    {
        $resource->attachTo($this, $path);
        $this->insertReference($path, '@'.$resource->getTargetPath());
    }

    /**
     * Add a target path (link or filesystem path) to the beginning of the stack in the store at a path.
     *
     * @param string $path
     * @param string $reference
     */
    private function insertReference($path, $reference)
    {
        if (!isset($this->json[$path])) {
            $this->json[$path] = array();
        }

        if (!in_array($reference, $this->json[$path], true)) {
            array_unshift($this->json[$path], $reference);
        }
    }

    private function getPathIterator($path)
    {
        return new FilterPathIterator(
            new ListDirectoriesIterator(
                new FollowLinksIterator(
                    new FilterReferencesIterator(
                        $this->json,
                        $path,
                        $this->baseDirectory
                    ),
                    $this->json,
                    $this->baseDirectory
                )
            ),
            $path
        );
    }

    private function getGlobIterator($query)
    {
        if (!Glob::isDynamic($query)) {
            return $this->getPathIterator($query);
        }

        return new DiscardDuplicateKeysIterator(
            new FilterMatchesIterator(
                new ListDirectoriesIterator(
                    new FollowLinksIterator(
                        new FilterReferencesIterator(
                            $this->json,
                            Glob::getBasePath($query),
                            $this->baseDirectory
                        ),
                        $this->json,
                        $this->baseDirectory
                    )
                ),
                Glob::toRegEx($query)
            )
        );
    }

    private function getChildIterator($path)
    {
        return new DiscardDuplicateKeysIterator(
            new FilterMatchesIterator(
                new ListDirectoriesIterator(
                    new FollowLinksIterator(
                        new FilterReferencesIterator(
                            $this->json,
                            $path,
                            $this->baseDirectory
                        ),
                        $this->json,
                        $this->baseDirectory
                    ),
                    // Limit the recursion to the depth of the path + 1
                    substr_count($path, '/') + 1
                ),
                '~^'.preg_quote(rtrim($path, '/'), '~').'/[^/]+$~'
            )
        );
    }

    private function getDeleteIterator($query)
    {
        return new FilterMatchesIterator(
            new FilterReferencesIterator(
                $this->json,
                Glob::getBasePath($query),
                $this->baseDirectory
            ),
            Glob::toRegEx($query)
        );
    }

//
//    /**
//     * Search for resources by querying their path.
//     *
//     * @param string $query           The glob query.
//     * @param bool   $firstResultOnly Should this method stop after finding a
//     *                             first result, for performances.
//     *
//     * @return ArrayResourceCollection The results of search.
//     */
//    private function getDirectChildren($path, $firstResultOnly = false)
//    {
//        // If the glob is dynamic, we search
//        $results = array();
//        $foundMatchingMappings = false;
//        $regExp = '~^'.preg_quote($path, '~').'/[^/]+$~';
//
//        for (
//            $reference = end($this->json), $mappedPath = key($this->json);
//            null !== $mappedPath;
//            $reference = prev($this->json), $mappedPath = key($this->json)
//        ) {
//            $mappedPath = rtrim($mappedPath, '/');
//
//            // We matched the path itself. Iterate through it.
//            if ($path === $mappedPath) {
//                $foundMatchingMappings = true;
//
//                // false: return all results
//                // true: follow links
//                // true: directories only
//                foreach ($this->expandReference($reference, false, true, true) as $filesystemPath) {
//                    $this->appendDirectoryEntries($path, $filesystemPath, $results, $firstResultOnly);
//                }
//
//                continue;
//            }
//
//            // We matched a child of the path
//            // e.g. /a/b/c of /a/b
//            if (preg_match($regExp, $mappedPath, $matches)) {
//                $foundMatchingMappings = true;
//
//                // true: return first result only
//                // true: follow links
//                // false: files and directories
//                foreach ($this->expandReference($reference, true, true, false) as $ref) {
//                    $results[$mappedPath] = $this->createResource($ref, $mappedPath);
//
//                    if ($firstResultOnly) {
//                        break;
//                    }
//                }
//
//                continue;
//            }
//
//            // We matched an ancestor of the path
//            // e.g. /a of /a/b
//            if (0 === strpos($path.'/', $mappedPath.'/')) {
//                $foundMatchingMappings = true;
//
//                // false: return all results
//                // true: follow links
//                // true: directories only
//                foreach ($this->expandReference($reference, false, true, true) as $filesystemPath) {
//                    // Does the directory exist under the ancestor?
//                    $directoryPath = substr_replace($path, $filesystemPath, 0, strlen($mappedPath));
//
//                    if (is_dir($directoryPath)) {
//                        $this->appendDirectoryEntries($path, $directoryPath, $results, $firstResultOnly);
//                    }
//                }
//            }
//
//            // We did not find anything but previously found mappings
//            // The mappings are sorted alphabetically, so we can safely abort
//            if ($foundMatchingMappings) {
//                break;
//            }
//        }
//
//        ksort($results);
//
//        return new ArrayResourceCollection(array_values($results));
//    }

//    /**
//     * @param string $path
//     * @param bool   $searchChildren
//     *
//     * @return string[]
//     *
//     * @throws ResourceNotFoundException
//     */
//    private function getReferences($path, $firstResultOnly = false, $searchChildren = true, $followLinks = false, $directoriesOnly = false)
//    {
//        $result = array();
//
//        // If the path is mapped in the JSON file, return it
//        if (isset($this->json[$path])) {
//            if (is_array($this->json[$path])) {
//                $result = $this->expandMergedDirectory(
//                    $this->json[$path],
//                    $firstResultOnly,
//                    $followLinks
//                );
//            } else {
//                $result = $this->expandSingleReference(
//                    $this->json[$path],
//                    $firstResultOnly,
//                    $followLinks,
//                    $directoriesOnly
//                );
//            }
//        }
//
//        if ($firstResultOnly && 1 === count($result)) {
//            return $result;
//        }
//
//        if (!$searchChildren) {
//            return $result;
//        }
//
//        // If the path is not mapped, look inside the other mappings
//        $basePath = $path;
//        $remainder = '';
//
//        while (false !== $pos = strrpos($basePath, '/')) {
//            $segment = substr($basePath, $pos + 1);
//            $basePath = substr($basePath, 0, $pos);
//            $remainder = '/'.$segment.$remainder;
//
//            // false: Return all results, not just the first one
//            // false: Don't search the children of the JSON mappings
//            // true: Follow and expand links to their final target
//            // true: Return directories only
//            foreach ($this->getReferences($basePath ?: '/', false, false, true, true) as $directoryPath) {
//                $filesystemPath = $directoryPath.$remainder;
//
//                if (file_exists($filesystemPath) && (!$directoriesOnly || is_dir($filesystemPath))) {
//                    $result[] = $filesystemPath;
//
//                    if ($firstResultOnly) {
//                        return $result;
//                    }
//                }
//            }
//        }
//
//        return $result;
//    }

//    private function expandReference($reference, $firstResultOnly = false, $followLinks = false, $directoriesOnly = false)
//    {
//        if (is_array($reference)) {
//            return $this->expandMergedDirectory($reference, $firstResultOnly, $followLinks);
//        }
//
//        return $this->expandSingleReference($reference, $firstResultOnly, $followLinks, $directoriesOnly);
//    }

    /**
     * @param string $reference
     *
     * @return string[]
     *
     * @throws ResourceNotFoundException
     */
//    private function expandSingleReference($reference, $firstResultOnly = false, $followLinks = false, $directoriesOnly = false)
//    {
//        if ('@' === substr($reference, 0, 1)) {
//            try {
//                if ($followLinks) {
//                    return $this->getReferences(
//                        substr($reference, 1),
//                        $firstResultOnly,
//                        true, // include children of mapped paths in the search
//                        $followLinks,
//                        $directoriesOnly
//                    );
//                }
//
//                return array($reference);
//            } catch (ResourceNotFoundException $e) {
//                // throw link not found
//            }
//        }
//
//        $filesystemPath = Path::makeAbsolute($reference, $this->baseDirectory);
//
//        if (!file_exists($filesystemPath)) {
//            // Houston we got a problem
//        }
//
//        if ($directoriesOnly && !is_dir($filesystemPath)) {
//            // error not a directory
//        }
//
//        return array($filesystemPath);
//    }

    /**
     * @param string[] $references
     * @param bool     $firstResultOnly
     * @param bool     $followLinks
     *
     * @return string[]
     */
//    private function expandMergedDirectory(array $references, $firstResultOnly = false, $followLinks = false)
//    {
//        $result = array();
//
//        foreach ($references as $reference) {
//            $filesystemPaths = $this->expandSingleReference(
//                $reference,
//                $firstResultOnly,
//                $followLinks,
//                true // directories only
//            );
//
//            if ($firstResultOnly && 1 === count($filesystemPaths)) {
//                return $filesystemPaths;
//            }
//
//            $result = array_merge($result, $filesystemPaths);
//        }
//
//        return $result;
//    }

    private function isMappedPath($path)
    {
        // The root is always considered mapped
        if ('/' === $path) {
            return true;
        }

        foreach ($this->json as $mappedPath => $reference) {
            if (0 === strpos($mappedPath.'/', $path.'/')) {
                return true;
            }
        }

        return false;
    }

    private function getDirectoryIterator($filesystemPath)
    {
        return new RecursiveDirectoryIterator(
            $filesystemPath,
            RecursiveDirectoryIterator::CURRENT_AS_PATHNAME | RecursiveDirectoryIterator::SKIP_DOTS
        );
    }

    private function appendDirectoryEntries($path, $filesystemPath, array &$results, $firstResultOnly = false)
    {
        $directoryIterator = $this->getDirectoryIterator($filesystemPath);

        foreach ($directoryIterator as $childFilesystemPath) {
            $childPath = substr_replace($childFilesystemPath, $path, 0, strlen($filesystemPath));

            if (!isset($results[$childPath])) {
                $results[$childPath] = $this->createResource($childFilesystemPath, $childPath);

                if ($firstResultOnly) {
                    break;
                }
            }
        }
    }
}
