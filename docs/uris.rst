URIs and Stream Wrappers
------------------------

This guide explains how to use multiple Puli_ repositories side by side. You
will also learn how to use Puli paths with PHP's file functions.

If you don't know what Puli is or why you should use it, read :doc:`at-a-glance`
first.

URI Repositories
----------------

Puli allows to use multiple repositories side by side through the
:class:`Puli\\Uri\\UriRepository` class, which assigns
:class:`Puli\\Repository\\ResourceRepositoryInterface` instances to URI scheme.
You can then use the :class:`Puli\\Uri\\UriRepository` like a regular repository,
except that you pass URIs instead of paths:

.. code-block:: php

    use Puli\Repository\ResourceRepository;
    use Puli\Uri\UriRepository;

    $repo = new ResourceRepository();
    $repo->add('/Acme/Blog', '/path/to/acme/blog/src');

    $uriRepo = new UriRepository();
    $uriRepo->register('composer', require_once __DIR__.'/vendor/resource-repository.php');
    $uriRepo->register('psr4', $repo);

    // uses the generated repository
    echo $uriRepo->get('composer:///acme/blog/css/style.css')->getContents();

    // /path/to/acme/blog/src/Blog.php
    echo $uriRepo->get('psr4:///Acme/Blog/Blog.php')->getContents();

In this example, the URI locator routes all requests for URIs with the
protocol "composer://" to one resource locator and requests for URIs with the
protocol "psr4://" to the other locator.

Usually it is a good idea to construct the repositories only when they are
actually needed. You can pass callbacks to
:method:`Puli\\Uri\\UriRepository::register` that construct the repository.
The callback will be invoked when the URI scheme is accessed for the first
time:

.. code-block:: php

    $uriRepo = new UriRepository();
    $uriRepo->register('composer', function () {
        return require_once __DIR__.'/vendor/resource-repository.php';
    });

You can use :method:`Puli\\Uri\\UriRepository::setDefaultScheme` to mark one
of the registered schemes as default scheme. The
:class:`Puli\\Uri\\UriRepository` prepends the default scheme whenever a path
without a scheme is passed:

.. code-block:: php

    // ...
    $uriRepo->setDefaultScheme('composer');

    echo $uriRepo->get('/acme/blog/css/style.css')->getContents();

    // same as
    echo $uriRepo->get('composer:///acme/blog/css/style.css')->getContents();

If you don't set a default scheme manually, the first registered scheme is used
as default scheme.

Stream Wrapper
--------------

Puli supports a `stream wrapper`_ that lets you access the contents of the
repository transparently through PHP's file functions. To register the wrapper,
call the :method:`Puli\\StreamWrapper\\ResourceStreamWrapper::register` method
:class:`Puli\\StreamWrapper\\ResourceStreamWrapper`and pass a configured
:class:`Puli\\Uri\\UriRepository` instance:

.. code-block:: php

    use Puli\Uri\UriRepository;
    use Puli\StreamWrapper\ResourceStreamWrapper;

    $uriRepo = new UriRepository();
    $uriRepo->register('composer', function () {
        return require_once __DIR__.'/vendor/resource-repository.php';
    });

    ResourceStreamWrapper::register($uriRepo);

You can now use regular PHP functions to access the files and directories in
the repository, as long as you prefix them with the URI scheme:

.. code-block:: php

    $contents = file_get_contents('composer:///acme/blog/css/style.css');

    foreach (scandir('composer:///acme/blog/') as $entry) {
        // ...
    }

.. _Puli: https://github.com/puli/puli
.. _stream wrapper: http://php.net/manual/en/intro.stream.php
