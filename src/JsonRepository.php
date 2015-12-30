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

    /**
     * Turns a list of references into a list of resources.
     *
     * The references are expected to be in the format returned by
     * {@link getReferencesForPath()}, {@link getReferencesForGlob()} and
     * {@link getReferencesInDirectory()}.
     *
     * The result contains Puli paths as keys and {@link PuliResource}
     * implementations as values. The order of the results is undefined.
     *
     * @param string[]|null[] $references The references indexed by Puli paths.
     *
     * @return array
     */
    private function createResources(array $references)
    {
        foreach ($references as $path => $reference) {
            $references[$path] = $this->createResource($reference, $path);
        }

        return $references;
    }

    /**
     * Returns the references for a given Puli path.
     *
     * Each reference returned by this method can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * an absolute filesystem path
     *
     * The result has either one entry or none, if no path was found. The key
     * of the single entry is the path passed to this method.
     *
     * @param string $path The Puli path.
     *
     * @return string[]|null[] A one-level array of references with Puli paths
     *                         as keys. The array has at most one entry.
     */
    private function getReferencesForPath($path)
    {
        // Stop on first result and flatten
        return $this->flatten($this->searchReferences($path, true));
    }

    /**
     * Returns the references matching a given Puli path glob.
     *
     * Each reference returned by this method can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * an absolute filesystem path
     *
     * The keys of the returned array are Puli paths. Their order is undefined.
     *
     * @param string $glob                The glob.
     * @param bool   $stopOnFirst         Whether to stop after finding a first
     *                                    result.
     * @param bool   $traverseDirectories Whether to search the contents of
     *                                    directories mapped in the JSON for
     *                                    matches.
     *
     * @return string[]|null[] A one-level array of references with Puli paths
     *                         as keys.
     */
    private function getReferencesForGlob($glob, $stopOnFirst = false, $traverseDirectories = true)
    {
        if (!Glob::isDynamic($glob)) {
            return $this->getReferencesForPath($glob);
        }

        return $this->flattenWithFilter(
            // Never stop on the first result before applying the filter since
            // the filter may reject the only returned path
            // Include nested path mappings and match them against the pattern
            $this->searchReferences(Glob::getBasePath($glob), false, true),
            Glob::toRegEx($glob),
            // Stop on first after applying the filter
            $stopOnFirst,
            // List directories and match their contents against the pattern
            $traverseDirectories
        );
    }

    /**
     * Returns the references in a given Puli path.
     *
     * Each reference returned by this method can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * an absolute filesystem path
     *
     * The keys of the returned array are Puli paths. Their order is undefined.
     *
     * @param string $path        The Puli path.
     * @param bool   $stopOnFirst Whether to stop after finding a first result.
     *
     * @return string[]|null[] A one-level array of references with Puli paths
     *                         as keys.
     */
    private function getReferencesInDirectory($path, $stopOnFirst = false)
    {
        return $this->flattenWithFilter(
            // Never stop on the first result before applying the filter since
            // the filter may reject the only returned path
            // Include nested path matches and test them against the pattern
            $this->searchReferences($path, false, true),
            '~^'.preg_quote(rtrim($path, '/'), '~').'/[^/]+$~',
            // Stop on first after applying the filter
            $stopOnFirst,
            // List directories and match their contents against the glob
            true,
            // Limit the directory exploration to the depth of the path + 1
            $this->getPathDepth($path) + 1
        );
    }

    /**
     * Flattens a two-level reference array into a one-level array.
     *
     * For each entry on the first level, only the first entry of the second
     * level is included in the result.
     *
     * Each reference returned by this method can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * an absolute filesystem path
     *
     * The keys of the returned array are Puli paths. Their order is undefined.
     *
     * @param array $references A two-level reference array as returned by
     *                          {@link searchReferences()}.
     *
     * @return string[]|null[] A one-level array of references with Puli paths
     *                         as keys.
     */
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

    /**
     * Flattens a two-level reference array into a one-level array and filters
     * out any references that don't match the given regular expression.
     *
     * This method takes a two-level reference array as returned by
     * {@link searchReferences()}. The references are scanned for Puli paths
     * matching the given regular expression. Those matches are returned.
     *
     * If a matching path refers to more than one reference, the first reference
     * is returned in the resulting array.
     *
     * If `$listDirectories` is set to `true`, all references that contain
     * directory paths are traversed recursively and scanned for more paths
     * matching the regular expression. This recursive traversal can be limited
     * by passing a `$maxDepth` (see {@link getPathDepth()}).
     *
     * Each reference returned by this method can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * an absolute filesystem path
     *
     * The keys of the returned array are Puli paths. Their order is undefined.
     *
     * @param array  $references          A two-level reference array as
     *                                    returned by {@link searchReferences()}.
     * @param string $regex               A regular expression used to filter
     *                                    Puli paths.
     * @param bool   $stopOnFirst         Whether to stop after finding a first
     *                                    result.
     * @param bool   $traverseDirectories Whether to search the contents of
     *                                    directory references for more matches.
     * @param int    $maxDepth            The maximum path depth when searching
     *                                    the contents of directory references.
     *                                    If 0, the depth is unlimited.
     *
     * @return string[]|null[] A one-level array of references with Puli paths
     *                         as keys.
     */
    private function flattenWithFilter(array $references, $regex, $stopOnFirst = false, $traverseDirectories = false, $maxDepth = 0)
    {
        $result = array();

        foreach ($references as $currentPath => $currentReferences) {
            // Check whether the current entry matches the pattern
            if (!isset($result[$currentPath]) && preg_match($regex, $currentPath)) {
                // If yes, the first stored reference is returned
                $result[$currentPath] = reset($currentReferences);

                if ($stopOnFirst) {
                    return $result;
                }
            }

            if (!$traverseDirectories) {
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

                        if ($stopOnFirst) {
                            return $result;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Filters the JSON file for all references relevant to a given search path.
     *
     * The JSON is scanned starting with the longest mapped Puli path.
     *
     * If the search path is "/a/b", the result includes:
     *
     *  * The references of the mapped path "/a/b".
     *  * The references of any mapped super path "/a" with the sub-path "/b"
     *    appended.
     *
     * If the argument `$includeNested` is set to `true`, the result
     * additionally includes:
     *
     *  * The references of any mapped sub path "/a/b/c".
     *
     * This is useful if you want to look for the children of "/a/b" or scan
     * all descendants for paths matching a given pattern.
     *
     * The result of this method is an array with two levels:
     *
     *  * The first level has Puli paths as keys.
     *  * The second level contains all references for that path, where the
     *    first reference has the highest, the last reference the lowest
     *    priority. The keys of the second level are integers. There may be
     *    holes between any two keys.
     *
     * The references of the second level contain:
     *
     *  * `null` values for virtual resources
     *  * strings starting with "@" for links
     *  * absolute filesystem paths for filesystem resources
     *
     * @param string $searchPath    The path to search.
     * @param bool   $stopOnFirst   Whether to stop after finding a first result.
     * @param bool   $includeNested Whether to include the references of path
     *                              mappings for nested paths.
     *
     * @return array An array with two levels.
     */
    private function searchReferences($searchPath, $stopOnFirst = false, $includeNested = false)
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
                    // The nested references already have size 1
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

    /**
     * Follows any link in a list of references.
     *
     * This method takes all the given references, checks for links starting
     * with "@" and recursively expands those links to their target references.
     * The target references may be `null` or absolute filesystem paths.
     *
     * Null values are returned unchanged.
     *
     * Absolute filesystem paths are returned unchanged.
     *
     * @param string[]|null[] $references  The references.
     * @param bool            $stopOnFirst Whether to stop after finding a first
     *                                     result.
     *
     * @return string[]|null[] The references with all links replaced by their
     *                         target references. If any link pointed to more
     *                         than one target reference, the returned array
     *                         is larger than the passed array (unless the
     *                         argument `$stopOnFirst` was set to `true`).
     */
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
            foreach ($this->searchReferences($referencedPath, $stopOnFirst) as $referencedReferences) {
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

    /**
     * Appends nested paths to references and filters out the existing ones.
     *
     * This method takes all the given references, appends the nested path to
     * each of them and then filters out the results that actually exist on the
     * filesystem.
     *
     * Null references are filtered out.
     *
     * Link references should be followed with {@link followLinks()} before
     * calling this method.
     *
     * @param string[]|null[] $references  The references.
     * @param string          $nestedPath  The nested path to append without
     *                                     leading slash ("/").
     * @param bool            $stopOnFirst Whether to stop after finding a first
     *                                     result.
     *
     * @return string[] The references with the nested path appended. Each
     *                  reference is guaranteed to exist on the filesystem.
     */
    private function appendPathAndFilterExisting(array $references, $nestedPath, $stopOnFirst = false)
    {
        $result = array();

        foreach ($references as $reference) {
            // Filter out null values
            // Links should be followed before calling this method
            if (null === $reference) {
                continue;
            }

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
     * Each reference passed in can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * a filesystem path relative to the base directory
     *  * an absolute filesystem path
     *
     * Each reference returned by this method can be:
     *
     *  * `null`
     *  * a link starting with `@`
     *  * an absolute filesystem path
     *
     * Additionally, the results are guaranteed to be an array. If the
     * argument `$stopOnFirst` is set, that array has a maximum size of 1.
     *
     * @param mixed $references  The reference(s).
     * @param bool  $stopOnFirst Whether to stop after finding a first result.
     *
     * @return string[]|null[] The resolved references.
     */
    private function resolveReferences($references, $stopOnFirst = false)
    {
        if (!is_array($references)) {
            $references = array($references);
        }

        foreach ($references as $key => $reference) {
            if (null !== $reference && !(isset($reference{0}) && '@' === $reference{0})) {
                $reference = Path::makeAbsolute($reference, $this->baseDirectory);

                // Ignore non-existing files. Not sure this is the right
                // thing to do.
                if (file_exists($reference)) {
                    $references[$key] = $reference;
                }
            }

            if ($stopOnFirst) {
                return $references;
            }
        }

        return $references;
    }

    /**
     * Returns the depth of a Puli path.
     *
     * The depth is used in order to limit the recursion when recursively
     * iterating directories.
     *
     * The depth starts at 0 for the root:
     *
     * /                0
     * /webmozart       1
     * /webmozart/puli  2
     * ...
     *
     * @param string $path A Puli path.
     *
     * @return int The depth starting with 0 for the root node.
     */
    private function getPathDepth($path)
    {
        // / has depth 0
        // /webmozart has depth 1
        // /webmozart/puli has depth 2
        // ...
        return substr_count(rtrim($path, '/'), '/');
    }
}
