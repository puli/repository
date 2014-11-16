Changelog
=========

* 1.0.0-alpha4 (@release_date@)

   * added prototypical `PuliAssetFactory` for Assetic
   * renamed `Path::dirname()` to `Path::getDirectory()`
   * added methods to `Path`:
      * `getRoot()`
      * `isAbsolute()`
      * `isRelative()`
      * `makeAbsolute()`
      * `makeRelative()`
      * `isLocal()`
   * added `PuliCssRewriteFilter` for Assetic
   * added `ResourceCollectionIterator` and `DirectoryResourceIterator`
   * added `ResourceFilterIterator`
   * added `TwigTemplateCacheWarmer`
   * renamed `PuliLoader` to `PuliTemplateLoader` for clarity
   * changed `PuliTemplateLoader::getCacheKey()` to prevent cache conflicts with
     templates loaded through a different loader
   * moved declarations in`ResourceRepositoryInterface` to a new
     `ManageableRepositoryInterface`
   * moved declarations in `ResourceLocatorInterface` to
     `ResourceRepositoryInterface` and removed `ResourceLocatorInterface`
   * all "locators" are now called "repositories"
   * rearranged the directory structure
   * made `ResourceInterface` independent of the filesystem. The filesystem
     specific methods are now in `LocalResourceInterface`
   * `getAlternativePaths()` is now called `getAllLocalPaths()`
   * all filesystem specific code was moved to the `Filesystem` namespace
   * added `getContents()`, `getSize()`, `getLastAccessedAt()` and 
     `getLastModifiedAt()` to `FileResourceInterface`
   * removed all pattern-related classes. This logic is now provided by the
     `Selector` class
   * `ResourceRepository::remove()`, `tag()` and `untag()` now return the number
     of affected resources
   * added `UriRepository::getDefaultScheme()` and `setDefaultScheme()`
   * removed `DirectoryResourceIterator`. The same functionality is provided
     by `ResourceCollectionIterator`
   * added interfaces `ResourceIteratorInterface` and `RecursiveResourceIteratorInterface`
   * added `RecursiveResourceIterator`
   * removed code from `ResourceFilterIterator` that is duplicated in
     `ResourceCollectionIterator`
   * moved all code to the `Puli\` top-level namespace
   * moved `ResourceRepository` and related classes to `Puli\Repository\`
   * renamed `getByTag()` to `findByTag()`
   * added `getLongestCommonBasePath()` and `isBasePath()` to `Path`
   * added `merge()` to `ResourceCollectionInterface`
   * added `CompositeRepository`

* 1.0.0-alpha3 (2014-02-22)

   * renamed `PhpResourceLocator` to `PhpCacheLocator`
   * renamed `PhpResourceLocatorDumper` to `PhpCacheDumper`
   * added `FilesystemLocator`
   * removed `ResourceDiscoveringInterface`
   * a base `ResourceLocatorInterface` can now be passed to `ResourceRepository`
   * instead of arrays, `ResourceCollection` objects are now returned everywhere
   * renamed `ResourceInterface::getPath()` to `getRealPath()`
   * renamed `ResourceInterface::getRepositoryPath()` to `getPath()`
   * added an extension for the templating engine Twig
   * added an extension for the Symfony Config and HttpKernel components

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
