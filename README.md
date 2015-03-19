The Puli Repository Component
=============================

[![Build Status](https://travis-ci.org/puli/repository.svg?branch=master)](https://travis-ci.org/puli/repository)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/puli/repository/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/puli/repository/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/65d650d5-04a3-41e7-bca0-bb83cae90e47/mini.png)](https://insight.sensiolabs.com/projects/65d650d5-04a3-41e7-bca0-bb83cae90e47)
[![Latest Stable Version](https://poser.pugx.org/puli/repository/v/stable.svg)](https://packagist.org/packages/puli/repository)
[![Total Downloads](https://poser.pugx.org/puli/repository/downloads.svg)](https://packagist.org/packages/puli/repository)
[![Dependency Status](https://www.versioneye.com/php/puli:repository/1.0.0/badge.svg)](https://www.versioneye.com/php/puli:repository/1.0.0)

Latest release: [1.0.0-beta3](https://packagist.org/packages/puli/repository#1.0.0-beta3)

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

The following [`Resource`] implementations are currently supported:

* [`GenericResource`]
* [`FileResource`]
* [`DirectoryResource`]

Read [Puli at a Glance] to learn more about Puli.

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
[Getting Started]: http://docs.puli.io/en/latest/getting-started.html
[Puli Documentation]: http://docs.puli.io/en/latest/index.html
[Puli at a Glance]: http://docs.puli.io/en/latest/at-a-glance.html
[issue tracker]: https://github.com/puli/issues/issues
[Git repository]: https://github.com/puli/repository
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
[`ResourceRepository`]: http://api.puli.io/latest/class-Puli.Repository.Api.ResourceRepository.html
[`InMemoryRepository`]: http://api.puli.io/latest/class-Puli.Repository.InMemoryRepository.html
[`FilesystemRepository`]: http://api.puli.io/latest/class-Puli.Repository.FilesystemRepository.html
[`NullRepository`]: http://api.puli.io/latest/class-Puli.Repository.NullRepository.html
[`Resource`]: http://api.puli.io/latest/class-Puli.Repository.Api.Resource.Resource.html
[`GenericResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.GenericResource.html
[`FileResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.FileResource.html
[`DirectoryResource`]: http://api.puli.io/latest/class-Puli.Repository.Resource.DirectoryResource.html
