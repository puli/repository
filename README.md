Locating Files with Puli
========================

[![Build Status](https://travis-ci.org/puli/repository.png?branch=master)](https://travis-ci.org/puli/repository)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/puli/repository/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/puli/repository/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/65d650d5-04a3-41e7-bca0-bb83cae90e47/mini.png)](https://insight.sensiolabs.com/projects/65d650d5-04a3-41e7-bca0-bb83cae90e47)
[![Latest Stable Version](https://poser.pugx.org/puli/repository/v/stable.png)](https://packagist.org/packages/puli/repository)
[![Total Downloads](https://poser.pugx.org/puli/repository/downloads.png)](https://packagist.org/packages/puli/repository)
[![Dependency Status](https://www.versioneye.com/php/puli:repository/1.0.0/badge.png)](https://www.versioneye.com/php/puli:repository/1.0.0)

Latest release: [1.0.0-alpha3](https://packagist.org/packages/puli/repository#1.0.0-alpha3)

PHP >= 5.3.9

Puli manages files and directories in a virtual repository. Whenever you need
to access these resources in your project, you can find them by their Puli path:

```php
use Puli\Repository\ResourceRepository;

$repo = new ResourceRepository();
$repo->add('/config', '/path/to/resources/config');

// /path/to/resources/config/routing.yml
echo $repo->get('/config/routing.yml')->getContents();
```

This is useful when you have to hard-code paths, for example in configuration
files:

```yaml
# config.yml
import: /config/routing.yml
```

Read [Puli at a Glance] if you want to learn more about Puli.

Authors
-------

* [Bernhard Schussek] a.k.a. [@webmozart]
* [The Community Contributors]

Installation
------------

Follow the [Getting Started] guide to install Puli in your project.

Documentation
-------------

Read the [Puli Documentation] if you want to learn more about Puli.

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

[Bernhard Schussek]: http://webmozarts.com
[The Community Contributors]: https://github.com/puli/repository/graphs/contributors
[Getting Started]: http://puli.readthedocs.org/en/latest/getting-started.html
[Puli Documentation]: http://puli.readthedocs.org/en/latest/index.html
[Puli at a Glance]: http://puli.readthedocs.org/en/latest/at-a-glance.html
[issue tracker]: https://github.com/puli/repository/issues
[Git repository]: https://github.com/puli/repository
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
