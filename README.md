The Puli Repository Component
=============================

[![Build Status](https://travis-ci.org/puli/repository.svg?branch=1.0)](https://travis-ci.org/puli/repository)
[![Build status](https://ci.appveyor.com/api/projects/status/a0g5jdtj78wv53c0/branch/1.0?svg=true)](https://ci.appveyor.com/project/webmozart/repository/branch/1.0)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/puli/repository/badges/quality-score.png?b=1.0)](https://scrutinizer-ci.com/g/puli/repository/?branch=1.0)
[![Latest Stable Version](https://poser.pugx.org/puli/repository/v/stable.svg)](https://packagist.org/packages/puli/repository)
[![Total Downloads](https://poser.pugx.org/puli/repository/downloads.svg)](https://packagist.org/packages/puli/repository)
[![Dependency Status](https://www.versioneye.com/php/puli:repository/1.0.0/badge.svg)](https://www.versioneye.com/php/puli:repository/1.0.0)

Latest release: [1.0.0-beta10](https://packagist.org/packages/puli/repository#1.0.0-beta10)

PHP >= 5.3.9

The [Puli] Repository Component provides an API for storing arbitrary resources
in a filesystem-like repository:

```php
use Puli\Repository\InMemoryRepository;
use Puli\Repository\Resource\DirectoryResource;

$repo = new InMemoryRepository();
$repo->add('/config', new DirectoryResource('/path/to/resources/config'));

// /path/to/resources/config/routing.yml
echo $repo->get('/config/routing.yml')->getBody();
```

The following [`ResourceRepository`] implementations are currently supported:

* [`InMemoryRepository`]
* [`FilesystemRepository`]
* [`NullRepository`]
* [`JsonRepository`]
* [`OptimizedJsonRepository`]

The following [`Resource`] implementations are currently supported:

* [`GenericResource`]
* [`FileResource`]
* [`DirectoryResource`]
* [`LinkResource`]

Authors
-------

* [Bernhard Schussek] a.k.a. [@webmozart]
* [The Community Contributors]

Installation
------------

Follow the [Getting Started] guide to install Puli in your project.

Documentation
-------------

Read the [Puli Documentation] to learn more about Puli.

Contribute
----------

Contributions to Puli are always welcome!

* Report any bugs or issues you find on the [issue tracker].
* You can grab the source code at Puli’s [Git repository].

Support
-------

If you are having problems, send a mail to bschussek@gmail.com or shout out to
[@webmozart] on Twitter.

License
-------

All contents of this package are licensed under the [MIT license].

[Puli]: http://puli.io
[Bernhard Schussek]: http://webmozarts.com
[The Community Contributors]: https://github.com/puli/repository/graphs/contributors
[Installation guide]: http://docs.puli.io/en/latest/installation.html
[Puli Documentation]: http://docs.puli.io/en/latest/index.html
[issue tracker]: https://github.com/puli/issues/issues
[Git repository]: https://github.com/puli/repository
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
[`ResourceRepository`]: http://api.puli.io/latest/class-Puli.Repository.Api.ResourceRepository.html
[`InMemoryRepository`]: http://api.puli.io/latest/class-Puli.Repository.InMemoryRepository.html
[`FilesystemRepository`]: http://api.puli.io/latest/class-Puli.Repository.FilesystemRepository.html
[`NullRepository`]: http://api.puli.io/latest/class-Puli.Repository.NullRepository.html
[`JsonRepository`]: http://api.puli.io/latest/class-Puli.Repository.JsonRepository.html
[`OptimizedJsonRepository`]: http://api.puli.io/latest/class-Puli.Repository.OptimizedJsonRepository.html
[`Resource`]: http://api.puli.io/latest/class-Puli.Repository.Api.Resource.Resource.html
[`GenericResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.GenericResource.html
[`FileResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.FileResource.html
[`DirectoryResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.DirectoryResource.html
[`LinkResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.LinkResource.html
