<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\StreamWrapper;

use Webmozart\Puli\Filesystem\Resource\LocalResourceInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Locator\UriLocatorInterface;
use Webmozart\Puli\Repository\UnsupportedOperationException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapper implements StreamWrapperInterface
{
    /**
     * @var UriLocatorInterface
     */
    private static $locator;

    /**
     * @var array
     */
    private static $schemes = array();

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var \IteratorIterator
     */
    private $directoryIterator;

    public static function register(UriLocatorInterface $locator)
    {
        if (null !== self::$locator) {
            throw new StreamWrapperException(
                'You can only register one URI locator with the '.
                'stream wrapper.'
            );
        }

        foreach ($locator->getRegisteredSchemes() as $scheme) {
            self::$schemes[$scheme] = true;

            stream_wrapper_register($scheme, __CLASS__);
        }

        self::$locator = $locator;
    }

    public static function unregister()
    {
        self::$locator = null;

        foreach (self::$schemes as $scheme => $foo) {
            stream_wrapper_unregister($scheme);
        }

        self::$schemes = array();
    }

    public function dir_opendir($uri, $options)
    {
        $this->directoryIterator = new \IteratorIterator(
            self::$locator->listDirectory($uri)
        );

        $this->directoryIterator->rewind();

        return true;
    }

    public function dir_closedir()
    {
        $this->directoryIterator = null;

        return false;
    }

    public function dir_readdir()
    {
        if (!$this->directoryIterator->valid()) {
            return false;
        }

        $name = $this->directoryIterator->current()->getName();

        $this->directoryIterator->next();

        return $name;
    }

    public function dir_rewinddir()
    {
        $this->directoryIterator->rewind();

        return true;
    }

    public function mkdir($uri, $mode, $options)
    {
        throw new UnsupportedOperationException(sprintf(
            'The creation of new directories through the stream wrapper is '.
            'not supported. Tried to create the directory "%s".',
            $uri
        ));
    }

    public function rename($uriFrom, $uriTo)
    {
        // validate whether the URL exists
        $this->getLocator()->get($uriFrom);

        throw new UnsupportedOperationException(sprintf(
            'The renaming of resources through the stream wrapper is not '.
            'supported. Tried to rename "%s" to "%s".',
            $uriFrom,
            $uriTo
        ));
    }

    public function rmdir($uri, $options)
    {
        $resource = $this->getLocator()->get($uri);

        throw new UnsupportedOperationException(sprintf(
            'The removal of directories through the stream wrapper is not '.
            'supported. Tried to remove "%s"%s.',
            $uri,
            $resource instanceof LocalResourceInterface
                ? sprintf(' which points to "%s"', $resource->getLocalPath())
                : ''
        ));
    }

    public function stream_cast($castAs)
    {
        return $this->handle;
    }

    public function stream_close()
    {
        assert(null !== $this->handle);

        return fclose($this->handle);
    }

    public function stream_eof()
    {
        assert(null !== $this->handle);

        return feof($this->handle);
    }

    public function stream_flush()
    {
        assert(null !== $this->handle);

        return fflush($this->handle);
    }

    public function stream_lock($operation)
    {
        assert(null !== $this->handle);

        return flock($this->handle, $operation);
    }

    public function stream_metadata($uri, $option, $value)
    {
        $resource = $this->getLocator()->get($uri);

        if (!$resource instanceof LocalResourceInterface) {
            return true;
        }

        $paths = $resource->getAlternativePaths();

        foreach ($paths as $path) {
            switch ($option) {
                case STREAM_META_TOUCH:
                    if (!touch($path, $value[0], $value[1])) {
                        return false;
                    }
                    break;

                case STREAM_META_OWNER:
                case STREAM_META_OWNER_NAME:
                    if (!chown($path, $value)) {
                        return false;
                    }
                    break;

                case STREAM_META_GROUP:
                case STREAM_META_GROUP_NAME:
                    if (!chgrp($path, $value)) {
                        return false;
                    }
                    break;

                case STREAM_META_ACCESS:
                    if (!chmod($path, $value)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    public function stream_open($uri, $mode, $options, &$openedPath)
    {
        $resource = $this->getLocator()->get($uri);

        if (!$resource instanceof LocalResourceInterface) {
            return false;
        }

        $openedPath = $resource->getLocalPath();

        $this->handle = fopen($openedPath, $mode, $options & STREAM_USE_PATH) ?: null;

        return null !== $this->handle;
    }

    public function stream_read($length)
    {
        assert(null !== $this->handle);

        return fread($this->handle, $length);
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        assert(null !== $this->handle);

        return 0 === fseek($this->handle, $offset, $whence);
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        // noop
    }

    public function stream_stat()
    {
        assert(null !== $this->handle);

        return fstat($this->handle);
    }

    public function stream_tell()
    {
        assert(null !== $this->handle);

        return ftell($this->handle);
    }

    public function stream_truncate($newSize)
    {
        assert(null !== $this->handle);

        return ftruncate($this->handle, $newSize);
    }

    public function stream_write($data)
    {
        assert(null !== $this->handle);

        return fwrite($this->handle, $data);
    }

    public function unlink($uri)
    {
        $resource = $this->getLocator()->get($uri);

        throw new UnsupportedOperationException(sprintf(
            'The removal of files through the stream wrapper is not '.
            'supported. Tried to remove "%s"%s.',
            $uri,
            $resource instanceof LocalResourceInterface
                ? sprintf(' which points to "%s"', $resource->getLocalPath())
                : ''
        ));
    }

    public function url_stat($uri, $flags)
    {
        try {
            $resource = $this->getLocator()->get($uri);

            if (!$resource instanceof LocalResourceInterface) {
                // same result as stat() returns on error
                return false;
            }

            $path = $resource->getLocalPath();

            if ($flags & STREAM_URL_STAT_LINK) {
                return lstat($path);
            }

            return stat($path);
        } catch (ResourceNotFoundException $e) {
            if ($flags & STREAM_URL_STAT_QUIET) {
                // same result as stat() returns on error
                return false;
            }

            throw $e;
        }
    }

    private function getLocator()
    {
        if (null === self::$locator) {
            throw new StreamWrapperException(
                'The stream wrapper has not been registered. Please call '.
                '\Webmozart\Puli\StreamWrapper\ResourceStreamWrapper::register() '.
                'first.'
            );
        }

        return self::$locator;
    }
}
