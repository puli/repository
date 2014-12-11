<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\StreamWrapper;

use Puli\Repository\Filesystem\Resource\LocalResourceInterface;
use Puli\Repository\NoDirectoryException;
use Puli\Repository\Resource\DirectoryResourceInterface;
use Puli\Repository\Resource\FileResourceInterface;
use Puli\Repository\Resource\Iterator\ResourceCollectionIterator;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\UnsupportedOperationException;
use Puli\Repository\UnsupportedResourceException;
use Puli\Repository\Uri\UriRepositoryInterface;

/**
 * Registers a PHP stream wrapper for a {@link UriRepositoryInterface}.
 *
 * To register the stream wrapper, call {@link register}:
 *
 * ```php
 * use Puli\Repository\ResourceRepository;
 * use Puli\Repository\StreamWrapper\ResourceStreamWrapper;
 * use Puli\Repository\Uri\UriRepository;
 *
 * $puliRepo = new ResourceRepository();
 *
 * $repo = new UriRepository();
 * $repo->register('puli', $puliRepo);
 *
 * ResourceStreamWrapper::register($repo);
 *
 * file_get_contents('puli:///css/style.css');
 * // => $puliRepo->get('/css/style.css')->getContents()
 * ```
 *
 * The stream wrapper can only be used for reading, not writing.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapper implements StreamWrapperInterface
{
    const DEVICE_ASSOC = 'dev';

    const DEVICE_NUM = 0;

    const INODE_ASSOC = 'ino';

    const INODE_NUM = 1;

    const MODE_ASSOC = 'mode';

    const MODE_NUM = 2;

    const NUM_LINKS_ASSOC = 'nlink';

    const NUM_LINK_NUM = 3;

    const UID_ASSOC = 'uid';

    const UID_NUM = 4;

    const GID_ASSOC = 'gid';

    const GID_NUM = 5;

    const DEVICE_TYPE_ASSOC = 'rdev';

    const DEVICE_TYPE_NUM = 6;

    const SIZE_ASSOC = 'size';

    const SIZE_NUM = 7;

    const ACCESS_TIME_ASSOC = 'atime';

    const ACCESS_TIME_NUM = 8;

    const MODIFY_TIME_ASSOC = 'mtime';

    const MODIFY_TIME_NUM = 9;

    const CHANGE_TIME_ASSOC = 'ctime';

    const CHANGE_TIME_NUM = 10;

    const BLOCK_SIZE_ASSOC = 'blksize';

    const BLOCK_SIZE_NUM = 11;

    const NUM_BLOCKS_ASSOC = 'blocks';

    const NUM_BLOCKS_NUM = 12;

    /**
     * @var UriRepositoryInterface
     */
    private static $repo;

    /**
     * @var array
     */
    private static $schemes = array();

    /**
     * @var array
     */
    private static $defaultStat = array(
        self::DEVICE_ASSOC => -1,
        self::DEVICE_NUM => -1,
        self::INODE_ASSOC => -1,
        self::INODE_NUM => -1,
        self::MODE_ASSOC => -1,
        self::MODE_NUM => -1,
        self::NUM_LINKS_ASSOC => -1,
        self::NUM_LINK_NUM => -1,
        self::UID_ASSOC => 0,
        self::UID_NUM => 0,
        self::GID_ASSOC => 0,
        self::GID_NUM => 0,
        self::DEVICE_TYPE_ASSOC => -1,
        self::DEVICE_TYPE_NUM => -1,
        self::SIZE_ASSOC => 0,
        self::SIZE_NUM => 0,
        self::ACCESS_TIME_ASSOC => -1,
        self::ACCESS_TIME_NUM => -1,
        self::MODIFY_TIME_ASSOC => -1,
        self::MODIFY_TIME_NUM => -1,
        self::CHANGE_TIME_ASSOC => -1,
        self::CHANGE_TIME_NUM => -1,
        self::BLOCK_SIZE_ASSOC => -1,
        self::BLOCK_SIZE_NUM => -1,
        self::NUM_BLOCKS_ASSOC => 0,
        self::NUM_BLOCKS_NUM => 0,
    );

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var \IteratorIterator
     */
    private $directoryIterator;

    /**
     * Registers a URI repository as PHP stream wrapper.
     *
     * @param UriRepositoryInterface $repo The repository.
     *
     * @throws StreamWrapperException If a repository was previously registered.
     *                                Only one repository can be registered at
     *                                a time. Call {@link unregister} to
     *                                unregister.
     */
    public static function register(UriRepositoryInterface $repo)
    {
        if (null !== self::$repo) {
            throw new StreamWrapperException(
                'You can only register one URI locator with the '.
                'stream wrapper.'
            );
        }

        foreach ($repo->getSupportedSchemes() as $scheme) {
            self::$schemes[$scheme] = true;

            stream_wrapper_register($scheme, __CLASS__);
        }

        self::$repo = $repo;
    }

    /**
     * Unregisters the stream wrapper.
     *
     * If no stream wrapper was registered, this method does nothing.
     */
    public static function unregister()
    {
        self::$repo = null;

        foreach (self::$schemes as $scheme => $foo) {
            stream_wrapper_unregister($scheme);
        }

        self::$schemes = array();
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function dir_opendir($uri, $options)
    {
        // Provoke ResourceNotFoundException if not found
        $directory = self::$repo->get($uri);

        if (!$directory instanceof DirectoryResourceInterface) {
            throw new NoDirectoryException($uri);
        }

        $this->directoryIterator = new ResourceCollectionIterator(
            $directory->listEntries(),
            ResourceCollectionIterator::CURRENT_AS_NAME
        );

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function dir_closedir()
    {
        $this->directoryIterator = null;

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function dir_readdir()
    {
        if (!$this->directoryIterator->valid()) {
            return false;
        }

        $name = $this->directoryIterator->current();

        $this->directoryIterator->next();

        return $name;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function dir_rewinddir()
    {
        $this->directoryIterator->rewind();

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function mkdir($uri, $mode, $options)
    {
        throw new UnsupportedOperationException(sprintf(
            'The creation of new directories through the stream wrapper is '.
            'not supported. Tried to create the directory "%s".',
            $uri
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function rename($uriFrom, $uriTo)
    {
        // validate whether the URL exists
        $this->getRepository()->get($uriFrom);

        throw new UnsupportedOperationException(sprintf(
            'The renaming of resources through the stream wrapper is not '.
            'supported. Tried to rename "%s" to "%s".',
            $uriFrom,
            $uriTo
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function rmdir($uri, $options)
    {
        // validate whether the URL exists
        $resource = $this->getRepository()->get($uri);

        throw new UnsupportedOperationException(sprintf(
            'The removal of directories through the stream wrapper is not '.
            'supported. Tried to remove "%s"%s.',
            $uri,
            $resource instanceof LocalResourceInterface
                ? sprintf(' which points to "%s"', $resource->getLocalPath())
                : ''
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_cast($castAs)
    {
        return $this->handle;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_close()
    {
        assert(null !== $this->handle);

        return fclose($this->handle);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_eof()
    {
        assert(null !== $this->handle);

        return feof($this->handle);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_flush()
    {
        assert(null !== $this->handle);

        return fflush($this->handle);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_lock($operation)
    {
        throw new UnsupportedOperationException(
            'The locking of files through the stream wrapper is not '.
            'supported.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_metadata($uri, $option, $value)
    {
        switch ($option) {
            case STREAM_META_TOUCH:
                throw new UnsupportedOperationException(sprintf(
                    'Touching files through the stream wrapper is not '.
                    'supported. Tried to touch "%s".',
                    $uri
                ));

            case STREAM_META_OWNER:
            case STREAM_META_OWNER_NAME:
                throw new UnsupportedOperationException(sprintf(
                    'Changing file ownership through the stream wrapper '.
                    'is not supported. Tried to chown "%s".',
                    $uri
                ));

            case STREAM_META_GROUP:
            case STREAM_META_GROUP_NAME:
                throw new UnsupportedOperationException(sprintf(
                    'Changing file groups through the stream wrapper '.
                    'is not supported. Tried to chgrp "%s".',
                    $uri
                ));

            case STREAM_META_ACCESS:
                throw new UnsupportedOperationException(sprintf(
                    'Changing file permissions through the stream wrapper '.
                    'is not supported. Tried to chmod "%s".',
                    $uri
                ));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_open($uri, $mode, $options, &$openedPath)
    {
        if ('r' !== $mode) {
            throw new UnsupportedOperationException(sprintf(
                'Resources can only be opened for reading. Tried to open "%s" '.
                'with mode "%s".',
                $uri,
                $mode
            ));
        }

        $resource = $this->getRepository()->get($uri);

        if (!$resource instanceof FileResourceInterface) {
            throw new UnsupportedResourceException(sprintf(
                'Can only open file resources for reading. Tried to open "%s" '.
                'of type %s which does not implement FileResourceInterface.',
                $uri,
                get_class($resource)
            ));
        }

        if ($resource instanceof LocalResourceInterface) {
            $this->handle = fopen($resource->getLocalPath(), 'r', $options & STREAM_USE_PATH) ?: null;

            return null !== $this->handle;
        }

        $this->handle = fopen('php://temp', 'r+', $options & STREAM_USE_PATH);
        fputs($this->handle, $resource->getContents());
        rewind($this->handle);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_read($length)
    {
        assert(null !== $this->handle);

        return fread($this->handle, $length);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        assert(null !== $this->handle);

        return 0 === fseek($this->handle, $offset, $whence);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        // noop
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_stat()
    {
        assert(null !== $this->handle);

        return fstat($this->handle);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_tell()
    {
        assert(null !== $this->handle);

        return ftell($this->handle);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_truncate($newSize)
    {
        assert(null !== $this->handle);

        return ftruncate($this->handle, $newSize);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function stream_write($data)
    {
        assert(null !== $this->handle);

        return fwrite($this->handle, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function unlink($uri)
    {
        throw new UnsupportedOperationException(sprintf(
            'The removal of files through the stream wrapper is not '.
            'supported. Tried to remove "%s".',
            $uri
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function url_stat($uri, $flags)
    {
        try {
            $resource = $this->getRepository()->get($uri);

            if ($resource instanceof LocalResourceInterface) {
                $path = $resource->getLocalPath();

                if ($flags & STREAM_URL_STAT_LINK) {
                    return lstat($path);
                }

                return stat($path);
            }

            if ($resource instanceof FileResourceInterface) {
                $stat = self::$defaultStat;
                $stat[self::SIZE_NUM] = $stat[self::SIZE_ASSOC] = $resource->getSize();
                $stat[self::ACCESS_TIME_NUM] = $stat[self::ACCESS_TIME_ASSOC] = $resource->getLastAccessedAt();
                $stat[self::MODIFY_TIME_NUM] = $stat[self::MODIFY_TIME_ASSOC] = $resource->getLastModifiedAt();

                return $stat;
            }

            // Return the default stats, otherwise file_exists() returns false
            // for non-local, non-file resources
            return self::$defaultStat;
        } catch (ResourceNotFoundException $e) {
            if ($flags & STREAM_URL_STAT_QUIET) {
                // Same result as stat() returns on error
                // file_exists() returns false for this resource
                return false;
            }

            throw $e;
        }
    }

    private function getRepository()
    {
        if (null === self::$repo) {
            throw new StreamWrapperException(
                'The stream wrapper has not been registered. Please call '.
                '\Puli\Repository\StreamWrapper\ResourceStreamWrapper::register() '.
                'first.'
            );
        }

        return self::$repo;
    }
}
