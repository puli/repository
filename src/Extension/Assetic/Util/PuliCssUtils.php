<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Assetic\Util;

use Assetic\Util\CssUtils;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliCssUtils
{
    public static function filterReferences($content, $callback)
    {
        return CssUtils::filterReferences($content, function($matches) use ($callback) {
            // The referenced path is a repository path
            // e.g. "/webmozart/puli/images/bg.png"
            $referencedPath = $matches['url'];

            // Ignore empty URLs
            if ('' === $referencedPath) {
                return $matches[0];
            }

            // Ignore non-local paths
            if (!\Webmozart\Puli\Path::isLocal($referencedPath)) {
                return $matches[0];
            }

            // Ignore "data:" URLs
            if (0 === strpos($referencedPath, 'data:')) {
                return $matches[0];
            }

            // If the referenced path is not absolute, resolve it relative to
            // the directory of the source file
            if (!\Webmozart\Puli\Path::isAbsolute($referencedPath)) {
                $referencedPath = \Webmozart\Puli\Path::makeAbsolute($referencedPath, $repoDir);
            }

            // The referenced asset must be known
            if (!array_key_exists($referencedPath, $pathMap)) {
                throw new AssetException(sprintf(
                    'The asset "%s" referenced in "%s" could not be found.',
                    $matches['url'],
                    $repoPath
                ));
            }

            // The target path of the referenced file must be set
            if (!$pathMap[$referencedPath]) {
                throw new AssetException(sprintf(
                    'The referenced path "%s" in "%s" cannot be resolved, because '.
                    'the target path of "%s" is not set.',
                    $matches['url'],
                    $repoPath,
                    $matches['url']
                ));
            }

            // The target path of the source file must be set
            if (!$targetPath) {
                throw new AssetException(sprintf(
                    'The referenced path "%s" in "%s" cannot be resolved, because '.
                    'the target path of "%s" is not set.',
                    $matches['url'],
                    $repoPath,
                    $repoPath
                ));
            }

            // Get the relative path from the source directory to the reference
            // e.g. "/css/style.css" + "/images/bg.png" = "../images/bg.png"
            $relativePath = \Webmozart\Puli\Path::makeRelative($pathMap[$referencedPath], $targetDir);

            return str_replace($matches['url'], $relativePath, $matches[0]);
        });
    }

    private function __construct()
    {
    }
}
