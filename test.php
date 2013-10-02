<?php
namespace Fwk\Core;

use Fwk\Core\Action\ControllerActionProxy;
use Fwk\Core\Action\CallableActionProxy;

require_once __DIR__ .'/vendor/autoload.php';

Application::factory("myApp")
->register('Test', new CallableActionProxy(function() {
    return "coucou";
}))
->register('Coucou', new ControllerActionProxy('Controller\\Coucou', 'show'))
->run();