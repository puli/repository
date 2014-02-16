Changelog
=========

* 1.0.0-alpha3 (@release_date@)

 * renamed `PhpResourceLocator` to `PhpCacheLocator`
 * renamed `PhpResourceLocatorDumper` to `PhpCacheDumper`
 * added `FilesystemLocator`

* 1.0.0-alpha2 (2014-02-14)

 * fixed "Maximum function nesting level" error on Windows
 * pushed minimum PHP version to 5.3.9
 * removed `TagInterface` and descending classes
 * added support for dot segments ("." and "..")
 * removed `CreationNotAllowedException`
 * removed `RemoveNotAllowedException`
 * removed `RenameNotAllowedException`
 * added `UnsupportedOperationException`
 * added `Path`
 * added `Uri`
 * added `UriLocatorInterface` and `UriLocator`
 * changed `ResourceStreamWrapper::register()` to take a `UriLocatorInterface`
   instance instead of a scheme and a resource locator

* 1.0.0-alpha1 (2014-02-04)

 * first alpha release
