Welcome to Puli's documentation!
================================

.. toctree::
   :hidden:

   at-a-glance
   getting-started
   working-with-resources
   repository-management
   uris
   extensions

Puli_ manages the files, directories and other resources of your project in a
filesystem-like repository. Whenever you need to access these resources, you can
find them by their *Puli path*:

.. code-block:: php

    use Puli\Repository\ResourceRepository;

    $repo = new ResourceRepository();
    $repo->add('/config', '/path/to/resources/config');

    // /path/to/resources/config/routing.yml
    echo $repo->get('/config/routing.yml')->getContents();

This is useful when you have to hard-code paths, for example in configuration
files:

.. code-block:: yaml

    # config.yml
    import: /config/routing.yml

Read :doc:`at-a-glance` to learn more about what Puli is and why you need it.

Installation
------------

Follow the :doc:`getting-started` guide to install Puli in your project.

Contents
--------

The documentation contains the following sections:

* :doc:`at-a-glance`
* :doc:`getting-started`
* :doc:`working-with-resources`
* :doc:`repository-management`
* :doc:`uris`
* :doc:`extensions`

Contribute
----------

Contributions to Puli are very welcome!

* Report any bugs or issues you find on the `issue tracker`_.
* You can grab the source code at Puli's `Git repository`_.

Support
-------

If you are having problems, send a mail to bschussek@gmail.com or shout out to
`@webmozart`_ on Twitter.

License
-------

Puli, its extensions and this documentation are licensed under the `MIT
license`_.

.. _Puli: https://github.com/puli/puli
.. _issue tracker: https://github.com/puli/puli/issues
.. _Git repository: https://github.com/puli/puli
.. _@webmozart: https://twitter.com/webmozart
.. _MIT license: https://github.com/puli/puli/blob/master/LICENSE
