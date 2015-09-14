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

/**
 * API of stream wrappers as supported by PHP.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see    http://php.net/manual/en/class.streamwrapper.php
 */
interface StreamWrapper
{
    public function dir_closedir();

    public function dir_opendir($url, $options);

    public function dir_readdir();

    public function dir_rewinddir();

    public function mkdir($url, $mode, $options);

    public function rename($urlFrom, $urlTo);

    public function rmdir($url, $options);

    public function stream_cast($castAs);

    public function stream_close();

    public function stream_eof();

    public function stream_flush();

    public function stream_lock($operation);

    public function stream_open($url, $mode, $options, &$openedPath);

    public function stream_read($length);

    public function stream_seek($offset, $whence = SEEK_SET);

    public function stream_set_option($option, $arg1, $arg2);

    public function stream_stat();

    public function stream_tell();

    public function stream_write($data);

    public function unlink($url);

    public function url_stat($url, $flags);
}
