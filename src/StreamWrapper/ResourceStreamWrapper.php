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

use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Repository\CreationNotAllowedException;
use Webmozart\Puli\Repository\RemovalNotAllowedException;
use Webmozart\Puli\Repository\RenameNotAllowedException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapper implements StreamWrapperInterface
{
    /**
     * @var ResourceLocatorInterface[]
     */
    private static $locators = array();

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var \IteratorIterator
     */
    private $directoryIterator;

    public static function register($protocol, ResourceLocatorInterface $locator)
    {
        if (isset(self::$locators[$protocol])) {
            throw new ProtocolAlreadyRegisteredException(sprintf(
                'The protocol "%s://" was already registered.',
                $protocol
            ));
        }

        self::$locators[$protocol] = $locator;

        stream_wrapper_register($protocol, __CLASS__);
    }

    public static function unregister($protocol)
    {
        if (!isset(self::$locators[$protocol])) {
            throw new ProtocolNotRegisteredException(sprintf(
                'The protocol "%s://" was not registered.',
                $protocol
            ));
        }

        stream_wrapper_unregister($protocol);

        unset(self::$locators[$protocol]);
    }

    public function dir_opendir($url, $options)
    {
        list($protocol, $path) = $this->parseUrl($url);

        $this->directoryIterator = new \ArrayIterator(
            self::$locators[$protocol]->listDirectory($path)
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

    public function mkdir($url, $mode, $options)
    {
        list ($protocol) = $this->parseUrl($url);

        throw new CreationNotAllowedException(sprintf(
            'Creating new directories under the "%s" protocol is not allowed. '.
            'Tried to create the directory "%s".',
            $protocol,
            $url
        ));
    }

    public function rename($urlFrom, $urlTo)
    {
        // validate whether the URL exists
        $this->resolvePath($urlFrom);

        list ($protocol) = $this->parseUrl($urlFrom);

        throw new RenameNotAllowedException(sprintf(
            'Resources provided by the "%s" protocol must not be renamed. '.
            'Tried to rename "%s" to "%s".',
            $protocol,
            $urlFrom,
            $urlTo
        ));
    }

    public function rmdir($url, $options)
    {
        list ($protocol) = $this->parseUrl($url);
        $path = $this->resolvePath($url);

        throw new RemovalNotAllowedException(sprintf(
            'Resources provided by the "%s" protocol must not be deleted. '.
            'Tried to remove "%s" which points to "%s".',
            $protocol,
            $url,
            $path
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

    public function stream_metadata($url, $option, $value)
    {
        $paths = $this->resolveAlternativePaths($url);

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

    public function stream_open($url, $mode, $options, &$openedPath)
    {
        $openedPath = $this->resolvePath($url);

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

    public function unlink($url)
    {
        list ($protocol) = $this->parseUrl($url);
        $path = $this->resolvePath($url);

        throw new RemovalNotAllowedException(sprintf(
            'Resources provided by the "%s" protocol must not be deleted. '.
            'Tried to remove "%s" which points to "%s".',
            $protocol,
            $url,
            $path
        ));
    }

    public function url_stat($url, $flags)
    {
        try {
            $path = $this->resolvePath($url);

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

    private function parseUrl($url)
    {
        if (!preg_match('~^(?P<scheme>\w+)://(?P<path>.+)$~', $url, $parsed)) {
            // This should never happen, given that PHP always passes valid
            // URLs to the methods of this wrapper.
            assert(false);
        }

        if (!isset(self::$locators[$parsed['scheme']])) {
            throw new \RuntimeException(sprintf(
                'Please use the method ResourceStreamWrapper::register() for '.
                'registering streams of this type. Registering the stream '.
                'manually with stream_wrapper_register() is not supported.'
            ));
        }

        return array($parsed['scheme'], $parsed['path']);
    }

    private function getResource($url)
    {
        list($protocol, $path) = $this->parseUrl($url);

        return self::$locators[$protocol]->get($path);
    }

    private function resolvePath($url)
    {
        return $this->getResource($url)->getPath();
    }

    private function resolveAlternativePaths($url)
    {
        return $this->getResource($url)->getAlternativePaths();
    }
}
