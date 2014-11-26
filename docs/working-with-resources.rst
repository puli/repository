Working with Resources
======================

This guide explains how to use the resources returned by the Puli repository.
If you do not have a working repository, read :doc:`getting-started`.

If you don't know what Puli is or why you should use it, read :doc:`at-a-glance`
first.

Accessing Resources
-------------------

You can access individual resources in the repository with the method
:method:`Puli\\Repository\\ResourceRepositoryInterface::get`:

.. code-block:: php

    echo $repo->get('/css/style.css')->getContents();

The :method:`Puli\\Repository\\ResourceRepositoryInterface::get` method accepts
the path of a resource and returns a :class:`Puli\\Resource\\ResourceInterface`.

If you want to retrieve multiple resources at once, use
:method:`Puli\\Repository\\ResourceRepositoryInterface::find`. This method
accepts a glob pattern and returns a
:class:`Puli\\Resource\\Collection\\ResourceCollectionInterface`:

.. code-block:: php

    foreach ($repo->find('/css/*')->getPaths() as $path) {
        echo $path;
    }

    // => /css/reset.css
    // => /css/style.css

You can check whether a resource exists by passing its path to
:method:`Puli\\Repository\\ResourceRepositoryInterface::contains`:

.. code-block:: php

    if ($repo->contains('/css/style.css')) {
        // ...
    }

Like :method:`Puli\\Repository\\ResourceRepositoryInterface::find`, this method
also accepts glob patterns. If you pass a glob, the method will return ``true``
only if at least one resource matched the pattern.

Resources
---------

The :method:`Puli\\Repository\\ResourceRepositoryInterface::get` method returns
:class:`Puli\\Resource\\ResourceInterface` instances. This interface lets you
access the name and the repository path of the resource:

.. code-block:: php

    $resource = $repo->get('/css/style.css');

    echo $resource->getName();
    // => style.css

    echo $resource->getPath();
    // => /css/style.css

Resources don't necessarily have to be located on the file system. But those
that do implement :class:`Puli\\Filesystem\\Resource\\LocalResourceInterface`,
which lets you access the file system path with
:method:`Puli\\Filesystem\\Resource\\LocalResourceInterface::getLocalPath`:

.. code-block:: php

    $resource = $repo->get('/css/style.css');

    echo $resource->getLocalPath();
    // => /path/to/res/assets/css/style.css

Files
-----

File resources implement the additional interface
:class:`Puli\\Resource\\FileResourceInterface`. With this interface, you can
access the contents and file size (in bytes):

.. code-block:: php

    $resource = $repo->get('/css/style.css');

    echo $resource->getContents();
    // => .container { ...

    echo $resource->getSize();
    // => 1049

If you want to cache the file,
:method:`Puli\\Resource\\FileResourceInterface::getLastModifiedAt` returns the
UNIX timestamp (seconds since January 1st, 1970) of when the file was last
modified:

.. code-block:: php

    if ($resource->getLastModified() > $cacheTimestamp) {
        // ...
    }

Directories
-----------

Directory resources implement the additional interface
:class:`Puli\\Resource\\DirectoryResourceInterface`. This way you can easily
distinguish directories from files:

.. code-block:: php

    use Puli\Resource\DirectoryResourceInterface;

    if ($resource instanceof DirectoryResourceInterface) {
        // ...
    }

You can access the contents of a directory with the methods
:method:`Puli\\Resource\\DirectoryResourceInterface::get`,
:method:`Puli\\Resource\\DirectoryResourceInterface::contains` and
:method:`Puli\\Resource\\DirectoryResourceInterface::listEntries`:

.. code-block:: php

    $resource = $directory->get('style.css');

    if ($directory->contains('style.css')) {
        // ...
    }

    foreach ($directory->listEntries() as $name => $resource) {
        // ...
    }

Resource Collections
--------------------

When you fetch multiple resources from the repository, they are returned
in a :class:`Puli\\Resource\\Collection\\ResourceCollectionInterface` instance.
Resource collections offer convenience methods for accessing the names and the
paths of all contained resources at once:

.. code-block:: php

    $resources = $locator->get('/css/*.css');

    print_r($resources->getNames());
    // Array
    // (
    //     [0] => reset.css
    //     [1] => style.css
    // )

    print_r($resources->getPaths());
    // Array
    // (
    //     [0] => /css/reset.css
    //     [1] => /css/style.css
    // )

Resource collections are traversable, countable and support
:phpclass:`ArrayAccess`. When you still need the collection as array, call
:method:`Puli\\Resource\\Collection\\ResourceCollectionInterface::toArray`:

.. code-block:: php

    $array = $resources->toArray();

Further Reading
---------------

* :doc:`mapping-resources` teaches you more about the configuration of
  your repository.
* :doc:`repositories` explains how to manage resource repositories manually.

.. _Puli: https://github.com/puli/puli
