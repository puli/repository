Locating Files with Puli
========================

[![Build Status](https://travis-ci.org/puli/puli.png?branch=master)](https://travis-ci.org/puli/puli)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/puli/puli/badges/quality-score.png?s=f1fbf1884aed7f896c18fc237d3eed5823ac85eb)](https://scrutinizer-ci.com/g/puli/puli/)
[![Code Coverage](https://scrutinizer-ci.com/g/puli/puli/badges/coverage.png?s=5d83649f6fc3a9754297da9dc0d997be212c9145)](https://scrutinizer-ci.com/g/puli/puli/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/728198dc-dc0f-4bab-b5c0-c0b4e2a55bce/mini.png)](https://insight.sensiolabs.com/projects/728198dc-dc0f-4bab-b5c0-c0b4e2a55bce)
[![Latest Stable Version](https://poser.pugx.org/puli/puli/v/stable.png)](https://packagist.org/packages/puli/puli)
[![Total Downloads](https://poser.pugx.org/puli/puli/downloads.png)](https://packagist.org/packages/puli/puli)
[![Dependency Status](https://www.versioneye.com/php/puli:puli/1.0.0/badge.png)](https://www.versioneye.com/php/puli:puli/1.0.0)

Latest release: [1.0.0-alpha3](https://packagist.org/packages/puli/puli#1.0.0-alpha3)

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

Puli and its documentation are licensed under the [MIT license].

[Getting Started]: http://puli.readthedocs.org/en/latest/getting-started.html
[Puli Documentation]: http://puli.readthedocs.org/en/latest/index.html
[issue tracker]: https://github.com/puli/puli/issues
[Git repository]: https://github.com/puli/puli
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
