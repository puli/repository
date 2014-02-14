Changelog
=========

* 1.0.0-alpha2

 * fixed "Maximum function nesting level" error on Windows
 * pushed minimum PHP version to 5.3.9
 * removed `TagInterface` and descending classes
 * added support for dot segments ("." and "..")
 * removed `\Webmozart\Puli\Repository\CreationNotAllowedException`
 * removed `\Webmozart\Puli\Repository\RemoveNotAllowedException`
 * removed `\Webmozart\Puli\Repository\RenameNotAllowedException`
 * added `\Webmozart\Puli\Repository\UnsupportedOperationException`
 * added `\Webmozart\Puli\Path\Path`
 * added `\Webmozart\Puli\Uri\Uri`
 * added `\Webmozart\Puli\Locator\UriLocatorInterface` and
   `\Webmozart\Puli\Locator\UriLocator`
 * changed `\Webmozart\Puli\StreamWrapper\ResourceStreamWrapper::register()`
   to take a `\Webmozart\Puli\Locator\UriLocatorInterface` instance instead
   of a scheme and a resource locator

* 1.0.0-alpha1 (2014-02-04)

 * first alpha release
