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

use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use Puli\Repository\Resource\LinkResource;
use RuntimeException;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonEncoder;
use Webmozart\PathUtil\Path;

/**
 * Abstract base for Path mapping repositories.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractJsonRepository extends AbstractEditableRepository
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $baseDirectory;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $schemaPath;

    /**
     * @var JsonEncoder
     */
    private $encoder;

    /**
     * Creates a new repository.
     *
     * @param string            $path          The path to the JSON file. If
     *                                         relative, it must be relative to
     *                                         the base directory.
     * @param string            $baseDirectory The base directory of the store.
     *                                         Paths inside that directory are
     *                                         stored as relative paths. Paths
     *                                         outside that directory are stored
     *                                         as absolute paths.
     * @param ChangeStream|null $changeStream  If provided, the repository will
     *                                         append resource changes to this
     *                                         change stream.
     */
    public function __construct($path, $baseDirectory, ChangeStream $changeStream = null)
    {
        parent::__construct($changeStream);

        $this->baseDirectory = $baseDirectory;
        $this->path = Path::makeAbsolute($path, $baseDirectory);
//        $this->schemaPath = realpath(__DIR__.'/../res/schema/path-mappings-schema-1.0.json');
        $this->encoder = new JsonEncoder();

        $this->createRoot();
    }

    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
        if (null === $this->data) {
            $this->load();
        }

        $path = $this->sanitizePath($path);

        if ($resource instanceof ResourceCollection) {
            $this->ensureDirectoryExists($path);

            foreach ($resource as $child) {
                $this->addResource($path.'/'.$child->getName(), $child);
            }

            $this->sortStore();

            return;
        }

        $this->ensureDirectoryExists(Path::getDirectory($path));
        $this->addResource($path, $resource);
        $this->sortStore();

        $this->flush();
    }

    /**
     * Add the resource (internal method after checks of add()).
     *
     * @param string       $path
     * @param PuliResource $resource
     */
    private function addResource($path, $resource)
    {
        if (!($resource instanceof FilesystemResource || $resource instanceof LinkResource)) {
            throw new UnsupportedResourceException(sprintf(
                'PathMapping repositories only supports FilesystemResource and LinkedResource. Got: %s',
                is_object($resource) ? get_class($resource) : gettype($resource)
            ));
        }

        // Don't modify resources attached to other repositories
        if ($resource->isAttached()) {
            $resource = clone $resource;
        }

        if ($resource instanceof LinkResource) {
            $this->addLinkResource($path, $resource);
        } else {
            $this->addFilesystemResource($path, $resource);
        }

        $this->appendToChangeStream($resource);
    }

    /**
     * Add the filesystem resource.
     *
     * @param string             $path
     * @param FilesystemResource $resource
     */
    abstract protected function addFilesystemResource($path, FilesystemResource $resource);

    /**
     * Add the link resource.
     *
     * @param string       $path
     * @param LinkResource $resource
     */
    abstract protected function addLinkResource($path, LinkResource $resource);

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (null === $this->data) {
            $this->load();
        }

        // Subtract root
        $removed = $this->countStore() - 1;

        $this->data = array();
        $this->createRoot();

        $this->flush();

        return $removed;
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     */
    protected function ensureDirectoryExists($path)
    {
        if (array_key_exists($path, $this->data)) {
            return;
        }

        // Recursively initialize parent directories
        if ('/' !== $path) {
            $this->ensureDirectoryExists(Path::getDirectory($path));
        }

        $this->data[$path] = null;
    }

    /**
     * Create the repository root.
     */
    protected function createRoot()
    {
        if (null === $this->data) {
            $this->load();
        }

        if (isset($this->data['/'])) {
            return;
        }

        $this->data['/'] = null;
    }

    /**
     * Count the number of elements in the store.
     *
     * @return int
     */
    protected function countStore()
    {
        return count($this->data);
    }

    /**
     * Sort the store by keys.
     */
    protected function sortStore()
    {
        ksort($this->data);
    }

    /**
     * Create a filesystem or generic resource.
     *
     * @param string|null $filesystemPath
     *
     * @return DirectoryResource|FileResource|GenericResource
     */
    protected function createResource($filesystemPath, $path = null)
    {
        // Link resource
        if (0 === strpos($filesystemPath, 'l:')) {
            return $this->createLinkResource(substr($filesystemPath, 2), $path);
        }

        // Filesystem resource
        if (is_string($filesystemPath)) {
            $filesystemPath = $this->resolveRelativePath($filesystemPath);

            if (file_exists($filesystemPath)) {
                return $this->createFilesystemResource($filesystemPath, $path);
            }
        }

        return $this->createVirtualResource($path);
    }

    /**
     * Create a link resource to another resource of the repository.
     *
     * @param string      $targetPath The target path.
     * @param string|null $path       The repository path.
     *
     * @return LinkResource The link resource.
     *
     * @throws RuntimeException If the targeted resource does not exist.
     */
    protected function createLinkResource($targetPath, $path = null)
    {
        $resource = new LinkResource($targetPath);
        $resource->attachTo($this, $path);

        return $resource;
    }

    /**
     * Create a resource using its filesystem path.
     *
     * If the filesystem path is a directory, a DirectoryResource will be created.
     * If the filesystem path is a file, a FileResource will be created.
     * If the filesystem does not exists, a GenericResource will be created.
     *
     * @param string      $filesystemPath The filesystem path.
     * @param string|null $path           The repository path.
     *
     * @return DirectoryResource|FileResource The created resource.
     *
     * @throws RuntimeException If the file / directory does not exist.
     */
    protected function createFilesystemResource($filesystemPath, $path = null)
    {
        $resource = null;

        if (is_dir($filesystemPath)) {
            $resource = new DirectoryResource($filesystemPath);
        } elseif (is_file($filesystemPath)) {
            $resource = new FileResource($filesystemPath);
        }

        if ($resource) {
            $resource->attachTo($this, $path);

            return $resource;
        }

        throw new RuntimeException(sprintf(
            'Trying to create a FilesystemResource on a non-existing file or directory "%s"',
            $filesystemPath
        ));
    }

    /**
     * @param string|null $path
     *
     * @return GenericResource
     */
    protected function createVirtualResource($path = null)
    {
        $resource = new GenericResource();
        $resource->attachTo($this, $path);

        return $resource;
    }

    /**
     * Transform a relative path into an absolute path.
     *
     * @param string $relativePath
     *
     * @return string
     */
    protected function resolveRelativePath($relativePath)
    {
        if (0 === strpos($relativePath, 'l:')) {
            // Link
            return $relativePath;
        }

        return Path::makeAbsolute($relativePath, $this->baseDirectory);
    }

    /**
     * Transform a collection of relative paths into a collection of absolute paths.
     *
     * @param string[] $relativePaths
     *
     * @return string[]
     */
    protected function resolveRelativePaths($relativePaths)
    {
        foreach ($relativePaths as $key => $relativePath) {
            $relativePaths[$key] = $this->resolveRelativePath($relativePath);
        }

        return $relativePaths;
    }

    protected function load()
    {
        $decoder = new JsonDecoder();
        $decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        $this->data = file_exists($this->path)
            ? $decoder->decodeFile($this->path, $this->schemaPath)
            : array();
    }

    protected function flush()
    {
        $this->encoder->encodeFile($this->data, $this->path, $this->schemaPath);
    }
}
