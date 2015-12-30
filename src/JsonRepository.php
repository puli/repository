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
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\LinkResource;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
        $references = $this->getReferencesForPath($path);

        // Might be null, don't use isset()
        if (array_key_exists($path, $references)) {
            return $this->createResource($references[$path], $path);
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
        $results = $this->createResources($this->getReferencesForGlob($query));

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

        // Stop on the first result
        $results = $this->getReferencesForGlob($query, true);

        return !empty($results);
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

        $checkResults = $this->getReferencesForGlob($query);
        $nonDeletablePaths = array();

        foreach ($checkResults as $path => $filesystemPath) {
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

        // Don't stop on the first result
        // Don't list directories. We only want to list the mappings that exist
        // in the JSON here.
        $deletedPaths = $this->getReferencesForGlob($query.'{,/**/*}', false, false);
        $removed = 0;

        foreach ($deletedPaths as $path => $filesystemPath) {
            $removed += 1 + count($this->getReferencesForGlob($path.'/**/*'));

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
        $removed = count($this->getReferencesForGlob('/**/*')) - 1;

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
        $results = $this->createResources($this->getReferencesInDirectory($path));

        if (empty($results)) {
            $pathResults = $this->getReferencesForPath($path);

            if (empty($pathResults)) {
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

        // Stop on the first result
        $results = $this->getReferencesInDirectory($path, true);

        if (empty($results)) {
            $pathResults = $this->getReferencesForPath($path);

            if (empty($pathResults)) {
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

    private function createResources(array $references)
    {
        foreach ($references as $path => $reference) {
            $references[$path] = $this->createResource($reference, $path);
        }

        return $references;
    }

    private function getReferencesForPath($path)
    {
        // Stop on first result and flatten
        return $this->flatten($this->filterReferences($path, true));
    }

    private function getReferencesForGlob($glob, $stopOnFirst = false, $listDirectories = true)
    {
        if (!Glob::isDynamic($glob)) {
            return $this->getReferencesForPath($glob);
        }

        return $this->flattenWithFilter(
            // Include nested path mappings and match them against the pattern
            $this->filterReferences(Glob::getBasePath($glob), $stopOnFirst, true),
            Glob::toRegEx($glob),
            // List directories and match their contents against the pattern
            $listDirectories
        );
    }

    private function getReferencesInDirectory($path, $stopOnFirst = false)
    {
        return $this->flattenWithFilter(
            // Include nested path matches and test them against the pattern
            $this->filterReferences($path, $stopOnFirst, true),
            '~^'.preg_quote(rtrim($path, '/'), '~').'/[^/]+$~',
            // List directories and match their contents against the glob
            true,
            // Limit the directory exploration to the depth of the path + 1
            $this->getPathDepth($path) + 1
        );
    }

    private function flatten(array $references)
    {
        $result = array();

        foreach ($references as $currentPath => $currentReferences) {
            if (!isset($result[$currentPath])) {
                $result[$currentPath] = reset($currentReferences);
            }
        }

        return $result;
    }

    private function flattenWithFilter(array $references, $regex, $listDirectories = false, $maxDepth = 0)
    {
        $result = array();

        foreach ($references as $currentPath => $currentReferences) {
            // Check whether the current entry matches the pattern
            if (!isset($result[$currentPath]) && preg_match($regex, $currentPath)) {
                // If yes, the first stored reference is returned
                $result[$currentPath] = reset($currentReferences);
            }

            if (!$listDirectories) {
                continue;
            }

            // First follow any links before we check which of them is a directory
            $currentReferences = $this->followLinks($currentReferences);
            $currentPath = rtrim($currentPath, '/');

            // Search the nested entries if desired
            foreach ($currentReferences as $baseFilesystemPath) {
                // Ignore null values and file paths
                if (!is_dir($baseFilesystemPath)) {
                    continue;
                }

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $baseFilesystemPath,
                        RecursiveDirectoryIterator::CURRENT_AS_PATHNAME
                            | RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                if (0 !== $maxDepth) {
                    $currentDepth = $this->getPathDepth($currentPath);
                    $maxIteratorDepth = $maxDepth - $currentDepth;

                    if ($maxIteratorDepth < 1) {
                        continue;
                    }

                    $iterator->setMaxDepth($maxIteratorDepth);
                }

                $basePathLength = strlen($baseFilesystemPath);

                foreach ($iterator as $nestedFilesystemPath) {
                    $nestedPath = substr_replace($nestedFilesystemPath, $currentPath, 0, $basePathLength);

                    if (!isset($result[$nestedPath]) && preg_match($regex, $nestedPath)) {
                        $result[$nestedPath] = $nestedFilesystemPath;
                    }
                }
            }
        }

        return $result;
    }

    private function followLinks(array $references, $stopOnFirst = false)
    {
        $result = array();

        foreach ($references as $key => $reference) {
            // Not a link
            if (!isset($reference{0}) || '@' !== $reference{0}) {
                $result[] = $reference;

                if ($stopOnFirst) {
                    return $result;
                }

                continue;
            }

            $referencedPath = substr($reference, 1);

            // Get all the file system paths that this link points to
            // and append them to the result
            foreach ($this->filterReferences($referencedPath, $stopOnFirst) as $referencedReferences) {
                // Follow links recursively
                $referencedReferences = $this->followLinks($referencedReferences);

                // Append all resulting target paths to the result
                foreach ($referencedReferences as $referencedReference) {
                    $result[] = $referencedReference;

                    if ($stopOnFirst) {
                        return $result;
                    }
                }
            }
        }

        return $result;
    }

    private function filterReferences($searchPath, $stopOnFirst = false, $includeNested = false)
    {
        $result = array();
        $foundMatchingMappings = false;
        $searchPathForTest = rtrim($searchPath, '/').'/';

        foreach ($this->json as $currentPath => $currentReferences) {
            $currentPathForTest = rtrim($currentPath, '/').'/';

            // We found a mapping that matches the search path
            // e.g. mapping /a/b for path /a/b
            if ($searchPathForTest === $currentPathForTest) {
                $foundMatchingMappings = true;
                $result[$currentPath] = $this->resolveReferences($currentReferences, $stopOnFirst);

                if ($stopOnFirst) {
                    return $result;
                }

                continue;
            }

            // We found a mapping that lies within the search path
            // e.g. mapping /a/b/c for path /a/b
            if ($includeNested && 0 === strpos($currentPathForTest, $searchPathForTest)) {
                $foundMatchingMappings = true;
                $result[$currentPath] = $this->resolveReferences($currentReferences, $stopOnFirst);

                if ($stopOnFirst) {
                    return $result;
                }

                continue;
            }

            // We found a mapping that is an ancestor of the search path
            // e.g. mapping /a for path /a/b
            if (0 === strpos($searchPathForTest, $currentPathForTest)) {
                $foundMatchingMappings = true;
                $nestedPath = substr($searchPath, strlen($currentPathForTest));
                $currentPathWithNested = rtrim($currentPath, '/').'/'.$nestedPath;

                // Follow links so that we can check the nested directories in
                // the final transitive link targets
                $currentReferencesResolved = $this->followLinks(
                    // Never stop on first, since appendNestedPath() might
                    // discard the first but accept the second entry
                    $this->resolveReferences($currentReferences, false),
                    // Never stop on first (see above)
                    false
                );

                // Append the path and check which of the resulting paths exist
                $nestedReferences = $this->appendPathAndFilterExisting(
                    $currentReferencesResolved,
                    $nestedPath,
                    $stopOnFirst
                );

                // None of the results exists
                if (empty($nestedReferences)) {
                    continue;
                }

                if ($stopOnFirst) {
                    return array($currentPathWithNested => $nestedReferences);
                }

                // We are traversing long keys before short keys
                // It could be that this entry already exists. In that case,
                // append our findings to the existing ones
                if (isset($result[$currentPathWithNested])) {
                    $result[$currentPathWithNested] = array_merge(
                        $result[$currentPathWithNested],
                        $nestedReferences
                    );
                } else {
                    $result[$currentPathWithNested] = $nestedReferences;
                }

                continue;
            }

            // We did not find anything but previously found mappings
            // The mappings are sorted alphabetically, so we can safely abort
            if ($foundMatchingMappings) {
                break;
            }
        }

        return $result;
    }

    private function appendPathAndFilterExisting(array $references, $nestedPath, $stopOnFirst = false)
    {
        $result = array();

        foreach ($references as $reference) {
            $nestedReference = rtrim($reference, '/').'/'.$nestedPath;

            if (file_exists($nestedReference)) {
                $result[] = $nestedReference;

                if ($stopOnFirst) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Resolves a list of references stored in the JSON.
     *
     * @param string|string[] $references The reference(s).
     *
     * @return string[]|null[] The references indexed by numeric keys. Each
     *                         reference is either a link starting with "@",
     *                         an absolute file system path to a file or
     *                         directory or `null` for virtual resources.
     */
    private function resolveReferences($references, $stopOnFirst = false)
    {
        $result = array();

        if (!is_array($references)) {
            $references = array($references);
        }

        foreach ($references as $key => $reference) {
            if (null === $reference) {
                $result[] = null;

                if ($stopOnFirst) {
                    return $result;
                }

                continue;
            }

            if (isset($reference{0}) && '@' === $reference{0}) {
                // Include links as they are
                $result[] = $reference;

                if ($stopOnFirst) {
                    return $result;
                }

                continue;
            }

            $filesystemPath = Path::makeAbsolute($reference, $this->baseDirectory);

            if (!file_exists($filesystemPath)) {
                // Houston we got a problem
            }

            $result[] = $filesystemPath;

            if ($stopOnFirst) {
                return $result;
            }
        }

        return $result;
    }

    private function getPathDepth($path)
    {
        // / has depth 0
        // /webmozart has depth 1
        // /webmozart/puli has depth 2
        // ...
        return substr_count(rtrim($path, '/'), '/');
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
}
