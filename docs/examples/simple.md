# Simple Application Example

In this example, we'll create a single-page PHP application using the minimum amount of code possible.

``` php
<?php
// we assume you've installed the dependencies using Composer
require_once __DIR__ .'/../vendor/autoload.php';

use Fwk\Core\Application;
use Fwk\Core\Action\ProxyFactory;

// create the application
$app    = new Application('HelloApp');

// register the action (closure)
$resp   = $app->register('Hello', ProxyFactory::factory(function($name = null) { 
    return 'Hello '. (empty($name) ? '!' : $name); }
))

// set it as the default action
->setDefaultAction('Hello')

// run and send the response
->run()
->send();
``` 

That's it!
Now just point your browser to your application (ie: http://localhost/app/index.php?name=Julien)