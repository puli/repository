Manual Repository Management
============================

This guide explains how to manage your Puli_ repository with manual PHP code.
Puli should be installed already. If it is not, follow the instructions in
:doc:`../getting-started/composer-agnostics`.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

Mapping Resources
-----------------

Resources can be added to the repository with the method
:method:`Puli\\Repository\\ManageableRepositoryInterface::add`. This method
expects a file path or a glob in the second argument. If you pass
a file path, that file or directory will be mapped to the Puli path in the
first argument:

.. code-block:: php

    use Puli\Repository\ResourceRepository;

    $repo = new ResourceRepository();
    $repo->add('/css', '/path/to/assets/css');

    // /path/to/assets/css
    $directory = $repo->get('/css');

If you pass a glob, all matched files are accessible *under* the Puli path:

.. code-block:: php

    $repo->add('/css', '/path/to/assets/css/*.css');

    // /path/to/assets/css/style.css
    echo $repo->get('/css/style.css')->getContents();

Files can e removed from the repository with
:method:`Puli\\Repository\\ManageableRepositoryInterface::remove`:

.. code-block:: php

    $repo->remove('/css');

Read-Only Repositories
----------------------

Building and configuring a repository is expensive and should not be done on
every request. For this reason, Puli supports repositories that are optimized
for reading. These repositories cannot be modified.

A very simple example is the :class:`Puli\\Filesystem\\PhpCacheRepository`. This
repository reads the resource paths from a set of PHP files, which are created
with the :method:`Puli\\Filesystem\\PhpCacheRepository::dumpRepository` method:

.. code-block:: php

    use Puli\Filesystem\PhpCacheRepository;
    use Puli\Repository\ResourceRepository;

    $repo = new ResourceRepository(),
    // add resources...

    PhpCacheRepository::dumpRepository($repo, '/path/to/cache');

Then create a :class:`Puli\\Filesystem\\PhpCacheRepository` and pass the path to
the directory where you dumped the PHP files:

.. code-block:: php

    $repo = new PhpCacheRepository('/path/to/cache');

    // /path/to/assets/css/style.css
    echo $repo->get('/css/style.css')->getContents();

Puli supports the following repository implementations:

===============================================  ======================================  ========
Repository                                       Description                             Writable
===============================================  ======================================  ========
:class:`Puli\\Repository\\ResourceRepository`    Manages resources in memory.            Yes
:class:`Puli\\Filesystem\\PhpCacheRepository`    Reads resources from dumped PHP files.  No
:class:`Puli\\Filesystem\\FilesystemRepository`  Reads resources from the filesystem.    No
===============================================  ======================================  ========

Tagging Resources
-----------------

Resources managed by Puli can be tagged. This is useful for marking resources
that support specific features. For example, you can tag all XLIFF translation
files that can be consumed by a class ``Acme\Translator``:

.. code-block:: php

    $repo->tag('/translations/*.xlf', 'acme/translator/xlf');

You can remove one or all tags from a resource using the
:method:`Puli\\Repository\\ManageableRepositoryInterface::untag` method:

.. code-block:: php

    // Remove the tag "acme/translator/xlf"
    $repo->untag('/translations/*.xlf', 'acme/translator/xlf');

    // Remove all tags
    $repo->untag('/translations/*.xlf');

You can get all files marked with a specific tag with the
:method:`Puli\\Repository\\ResourceRepositoryInterface::getByTag` method:

.. code-block:: php

    $resources = $repo->getByTag('acme/translator/xlf');

Use :method:`Puli\\Repository\\ResourceRepositoryInterface::getTags` to read all
tags that have been registered with the repository:

.. code-block:: php

    $tags = $repo->getTags();

This method will return an array of strings, i.e. the names of all registered
tags.

Repository Backends
-------------------

The :class:`Puli\\Repository\\ResourceRepository` expects a *backend repository*
to be passed to its constructor. If you pass none, a
:class:`Puli\\Filesystem\\FilesystemRepository` is used by default:

.. code-block:: php

    use Puli\\Filesystem\\FilesystemRepository;
    use Puli\Repository\ResourceRepository;

    $backend = new FilesystemRepository();
    $repo = new ResourceRepository($backend);

Whenever you call :method:`Puli\\Repository\\ManageableRepositoryInterface::add()`,
the backend is used to lookup the added resources:

.. code-block:: php

    // ...
    $repo->add('/css', '/path/to/assets/css');

    // same as
    $repo->add('/css', $backend->find('/path/to/assets/css');

This is very useful, because :class:`Puli\\Filesystem\\FilesystemRepository`
expects a *root path* in its own constructor. When a root path is set, all
other paths are read relative to that root path:

.. code-block:: php

    // ...
    $backend = new FilesystemRepository('/path/to/project');
    $repo = new ResourceRepository($backend);

    // /path/to/project/assets/css
    $repo->add('/css', '/assets/css');

    // /path/to/project/resources
    $repo->add('/', '/resources');

Every class implementing :class:`Puli\\Repository\\ResourceRepositoryInterface`
can be used as backend. You can also implement your own backend, if you like.

Adding Resource Instances
-------------------------

Finally, instead of relying on the backend, you can construct and pass resources
manually:

.. code-block:: php

    use Puli\Filesystem\Resource\LocalDirectoryResource;

    $repo->add('/css', new LocalDirectoryResource('/path/to/assets/css'));

The passed resources must implement
:class:`Puli\\Resource\\AttachableResourceInterface`. Here is a list of all
resources implemented in Puli core:

===========================================================  ======================================
Repository                                                   Description
===========================================================  ======================================
:class:`Puli\\Resource\\DirectoryResource`                   A virtual directory in the repository.
:class:`Puli\\Filesystem\\Resource\\LocalDirectoryResource`  A directory on the file system.
:class:`Puli\\Filesystem\\Resource\\LocalFileResource`       A file on the file system.
===========================================================  ======================================

Further Reading
---------------

.. _Puli: https://github.com/puli/puli
