# Fwk\Core (Application Framework)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fwk/Core/badges/quality-score.png?s=a670a13e6ce2ed3ab8f8258b3a627576b29bbf61)](https://scrutinizer-ci.com/g/fwk/Core/)
[![Build Status](https://secure.travis-ci.org/fwk/Core.png?branch=master)](http://travis-ci.org/fwk/Core)
[![Code Coverage](https://scrutinizer-ci.com/g/fwk/Core/badges/coverage.png?s=1343ad093d37329f0c9de4a76ecde25fa5a2f20e)](https://scrutinizer-ci.com/g/fwk/Core/)
[![Latest Stable Version](https://poser.pugx.org/fwk/core/v/stable.png)](https://packagist.org/packages/fwk/core) 
[![Total Downloads](https://poser.pugx.org/fwk/core/downloads.png)](https://packagist.org/packages/fwk/core) 
[![Latest Unstable Version](https://poser.pugx.org/fwk/core/v/unstable.png)](https://packagist.org/packages/fwk/core) 
[![License](https://poser.pugx.org/fwk/core/license.png)](https://packagist.org/packages/fwk/core)

Core is a zero-configuration application framework that makes developers happy :)

## Installation

Via [Composer](http://getcomposer.org):

```
{
    "require": {
        "fwk/core": "dev-master",
    }
}
```

If you don't use Composer, you can still [download](https://github.com/fwk/Core/zipball/master) this repository and add it
to your ```include_path``` [PSR-0 compatible](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)

## Introduction

Core can be used diffently depending on your application needs and how you plan to maintain and make it evolves in time. There is no directory-structure dependencies nor "recommended pattern". Knowing how to configure PHP 5.3+ on your environment is the only prerequisite.

A Request to an _Application_ calls an _Action_ (Controller) which sometimes uses _Services_ (Model) to return a _Result_ (View). Fwk\Core let you use any type of Action thanks to _ActionProxies_. An object containing the _Request_ (and the _Response_) is shared during the runtime, it is the _Context_. The runtime creates _Events_ emitted by the Application that can be used by _Listeners_ to extends its behavior. 

Included ActionProxies are:

* CallableActionProxy(```callable```): calls ```callable```
* ControllerActionProxy(```className```,```methodName```): instanciate ```className``` and calls ```methodName```
* IncludeActionProxy(```file.php```): includes ```file.php```
* ServiceActionProxy(```serviceName```): executes the Service registered as ```serviceName```
* ServiceControllerActionProxy(```serviceName```, ```methodName```): executes ```methodName``` on the Service registered as ```serviceName```

Its suggested to use ```Fwk\Core\Action\ProxyFactory``` as a shortcut to the corresponding ```Fwk\Core\ActionProxy```, like:

```php
$app->register('Hello', ProxyFactory::factory(function() { /* ... */ })); // CallableActionProxy
$app->register('Hello', ProxyFactory::factory('+file.php')); // IncludeActionProxy
$app->register('Hello', ProxyFactory::factory('HelloWorld\\HelloController:show')); // ControllerActionProxy
$app->register('Hello', ProxyFactory::factory('@service')); // ServiceActionProxy
$app->register('Hello', ProxyFactory::factory('@service:method')); // ServiceControllerActionProxy
```

### Hello World Application

This is probably the simplest example:

``` php
<?php
namespace HelloWorld;

// we're index.php in the 'public' http folder (the doc_root)
require __DIR__ .'/../vendor/autoload.php';

$app = new \Fwk\Core\Application('helloWorld');

// the easy way
$app['Hello'] = function($name = null) {
    return 'Hello '. (!empty($name) ? $name : 'World');
};

// the above is a shortcut to this:
$app->register(
    'Hello', 
    new \Fwk\Core\Action\CallableActionProxy(
        function($name = null) {
            return 'Hello '. (!empty($name) ? $name : 'World');
        }
    )
);

// The  is needed to respond to / (or index.php)
$app->setDefaultAction('Hello');

// execute
$response = $app->run();
if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
    $response->send();
} else {
    echo $response;
}
```

That's it! Now open your browser to http://localhost/wherever/index.php or http://localhost/wherever/index.php?name=John+Doe !

More documentation on its way...

## Contributions / Community

- Issues on Github: https://github.com/fwk/Events/issues
- Follow *Fwk* on Twitter: [@phpfwk](https://twitter.com/phpfwk)

## Legal 

Fwk is licensed under the 3-clauses BSD license. Please read CREDITS and LICENSE for full details.
