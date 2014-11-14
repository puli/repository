.. index::
    single: Getting Started
    single: Installation

.. |trade| unicode:: U+2122

Getting Started
===============

We have several guides that show how to get started quickly with Puli_. If you
don't know what Puli is or why you should use it, read :doc:`at-a-glance` first.

Requirements
------------

Puli requires PHP 5.3.9 or higher.

Stability
---------

Puli is not yet available in a stable version. The latest release is
|release|.

Guides
------

.. toctree::
   :hidden:

   getting-started/application-devs
   getting-started/package-devs
   getting-started/composer-agnostics

We have different "Getting Started" guides for different kinds of developers:

* :doc:`getting-started/application-devs` explains how to use Puli in
  applications that use Composer_ to load their required packages.

* :doc:`getting-started/package-devs` explains how to use Puli in reusable
  Composer packages, sometimes also called "libraries".

* :doc:`getting-started/composer-agnostics` explains how to setup Puli without
  the Composer plugin. Read this if you want to Do It Yourself\ |trade|.

If you are not sure, start with :doc:`getting-started/application-devs`.

.. _Puli: https://github.com/puli/puli
.. _Composer: https://getcomposer.org
