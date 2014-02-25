<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Assetic\Filter;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\AssetManager;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\HashableInterface;
use Assetic\Util\CssUtils;
use Webmozart\Puli\Extension\Assetic\Asset\PuliAssetInterface;
use Webmozart\Puli\Extension\Assetic\AssetException;
use Webmozart\Puli\Path\Path;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliCssRewriteFilter implements FilterInterface, HashableInterface
{
    /**
     * @var AssetManager
     */
    private $am;

    public function __construct(AssetManager $am)
    {
        $this->am = $am;
    }

    /**
     * Not implemented.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * Filters an asset just before it's dumped.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterDump(AssetInterface $asset)
    {
        if (!$asset instanceof PuliAssetInterface) {
            return;
        }

        $pathMap = array();

        // Get a map of repository paths to target paths
        // e.g. "/webmozart/puli/images/bg.png" => "/images/bg.png"
        foreach ($this->am->getNames() as $name) {
            $this->extractTargetPaths($this->am->get($name), $pathMap);
        }

        // Remember the repository dir of the current resource
        $repoPath = $asset->getSourcePath();
        $repoDir = Path::getDirectory($repoPath);

        // Get the target directory of the current resource
        // e.g. "css"
        $targetPath = $asset->getTargetPath();
        $targetDir = Path::getDirectory($targetPath);

        // Convert to an absolute path so that we can create a proper
        // relative path later on
        // e.g. "/css"
        if (!Path::isAbsolute($targetDir)) {
            $targetDir = '/'.$targetDir;
        }

        $content = CssUtils::filterReferences($asset->getContent(), function($matches) use ($pathMap, $repoDir, $repoPath, $targetDir, $targetPath) {
            // The referenced path is a repository path
            // e.g. "/webmozart/puli/images/bg.png"
            $referencedPath = $matches['url'];

            // Ignore empty URLs
            if ('' === $referencedPath) {
                return $matches[0];
            }

            // Ignore non-local paths
            if (!Path::isLocal($referencedPath)) {
                return $matches[0];
            }

            // Ignore "data:" URLs
            if (0 === strpos($referencedPath, 'data:')) {
                return $matches[0];
            }

            // If the referenced path is not absolute, resolve it relative to
            // the directory of the source file
            if (!Path::isAbsolute($referencedPath)) {
                $referencedPath = Path::makeAbsolute($referencedPath, $repoDir);
            }

            // The referenced asset must be known
            if (!array_key_exists($referencedPath, $pathMap)) {
                throw new AssetException(sprintf(
                    'The asset "%s" referenced in "%s" could not be found.',
                    $referencedPath,
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
            $relativePath = Path::makeRelative($pathMap[$referencedPath], $targetDir);

            return str_replace($matches['url'], $relativePath, $matches[0]);
        });

        $asset->setContent($content);
    }

    private function extractTargetPaths(AssetInterface $asset, &$array)
    {
        if ($asset instanceof PuliAssetInterface) {
            $targetPath = $asset->getTargetPath();

            // All relative paths are treated like absolute paths
            // Don't change empty paths so that we can throw an exception
            // later
            if ($targetPath && !Path::isAbsolute($targetPath)) {
                $targetPath = '/'.$targetPath;
            }

            $array[$asset->getSourcePath()] = $targetPath;
        } elseif ($asset instanceof AssetCollection) {
            foreach ($asset as $entry) {
                $this->extractTargetPaths($entry, $array);
            }
        }
    }

    /**
     * Generates a hash for the object
     *
     * @return string Object hash
     */
    public function hash()
    {
        $am = $this->am;
        $this->am = null;
        $hash = serialize($this);
        $this->am = $am;

        return $hash;
    }
}
