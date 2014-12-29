Changelog
=========

* 1.0.0-alpha5 (@release_date@)

 * removed `Selector::toGlob()`
 * added `Selector::getBasePath()`
 * changed `ResourceRepositoryInterface::find()` to match directory separators
   "/" when given a wildcard "*"
 * added `ResourceRepositoryInterface::listDirectory()`
 * implemented escaping for globs:
   * "*" matches any character, including "/"
   * "\*" matches "*" (must be written as '\\*' in PHP)
   * "\\" matches "\" (must be written as '\\\\' in PHP)
 * removed `createAttached()` from `DirectoryResource`, `LocalFileResource` and
   `LocalDirectoryResource`
 * removed `AttachableResourceInterface`
 * added methods to `ResourceInterface`:
   * `attachTo()`
   * `detach()`
   * `getRepository()`
   * `getRepositoryPath()`
   * `isAttached()`
   * `override()`
   * `createReference()`
   * `isReference()`
 * `ResourceInterface` now extends `\Serializable`
 * renamed `LocalResource` to `AbstractLocalResource`
 * added `GenericResource`
 * `CompositeRepository` now sets the correct path for returned resources
 * moved `Selector` to `Puli\Repository\Selector` namespace
 * removed tagging, which is out of scope of this package
 * made `GenericResource` properties private
 * renamed:
   * `ResourceRepository` to `InMemoryRepository`
   * `ResourceCollection` to `ArrayResourceCollection`
   * `DirectoryResource` to `VirtualDirectoryResource`
   * `RecursiveResourceIterator` to `RecursiveResourceIteratorIterator`
 * removed `Interface` suffixes of all interfaces
 * removed `UriRepository`
 * added `$scheme` argument to `ResourceStreamWrapper::register()` and
   `ResourceStreamWrapper::unregister()`
 * added `ResourceNotFoundException::forPath()`
 * added `NoDirectoryException::forPath()`
 * added `DirectoryResource::count()`
 * added `FileCopyRepository`
 * moved contents of `Puli\Repository\Filesystem\Iterator` to `Puli\Repository\Iterator`
 * moved contents of `Puli\Repository\Filesystem\Resource` to `Puli\Repository\Resource`
 * moved `FilesystemRepository` to `Puli\Repository`
 * removed `PhpCacheRepository`
 * added domain-specific `Assertion` class
 * removed `LocalResource::getAllLocalPaths`
 * moved API interfaces to `Api` sub-namespace
 * removed notions of "directories" and "files". All resources can have children
   and a body now.
 * renamed `Selector` to `Glob`
 * moved `Glob` and related iterators to "webmozart/glob" package
 * added `EditableRepository::clear()`
 * merged `GenericResource` and `AbstractResource`
 * removed backend repositories from `InMemoryRepository` and `FilesystemRepository`

* 1.0.0-alpha4 (2014-12-03)

 * moved extensions to separate repositories in https://github.com/puli
 * moved documentation to separate repository: https://github.com/puli/docs
 * moved `Path` to "webmozart/path-util" package
 * moved all code to `Puli\Repository` namespace
 * rearranged the directory structure
 * added `ResourceCollectionIterator`
 * added `ResourceIteratorInterface`
 * added `RecursiveResourceIterator`
 * added `RecursiveResourceIteratorInterface`
 * added `ResourceFilterIterator`
 * renamed `ResourceRepositoryInterface` to `ManageableRepositoryInterface`
 * renamed `ResourceLocatorInterface` to `ResourceRepositoryInterface`
 * renamed all "locators" to "repositories"
 * moved all filesystem specific code to `Filesystem` namespace
 * made `ResourceInterface` independent of the filesystem. The filesystem
   specific methods are now in `LocalResourceInterface`
 * `getAlternativePaths()` is now called `getAllLocalPaths()`
 * added `getContents()`, `getSize()`, `getLastAccessedAt()` and
   `getLastModifiedAt()` to `FileResourceInterface`
 * removed all pattern-related classes. This logic is now provided by the
   `Selector` class
 * `ResourceRepository::remove()`, `tag()` and `untag()` now return the number
   of affected resources
 * added `UriRepository::getDefaultScheme()` and `setDefaultScheme()`
 * renamed `getByTag()` to `findByTag()`
 * added `merge()` to `ResourceCollectionInterface`
 * added `CompositeRepository`
 * removed `LazyDirectoryResource`
 * fixed ResourceRepository::add() to be deterministic when selectors are passed. Closes #17

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
