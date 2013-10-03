<?php
namespace Fwk\Core;

use Fwk\Core\Action\ControllerActionProxy;
use Fwk\Core\Action\CallableActionProxy;
use Fwk\Di\Definitions\ClassDefinition;

require_once __DIR__ .'/vendor/autoload.php';

// app
$app = Application::factory("myApp")
->addListener(new Components\RequestMatcher\RequestMatcherListener('requestMatcher'))
->addListener(new Components\ErrorReporterListener(array(
    'application_root' => dirname(__FILE__),
    'display_line_numbers' => true,
    'server_name' => 'DEV',
    'ignore_folders' => array(
        dirname(__FILE__) . DIRECTORY_SEPARATOR . '/vendor'
    ),
    'enable_saving' => false,
    'catch_ajax_errors' => true,
    'snippet_num_lines' => 10
)))
->addListener(new Components\SessionListener())
->register('Test', new CallableActionProxy(function() {
    return "coucou";
}))
->register('Coucou', new ControllerActionProxy('Controller\\Coucou', 'show'));

// services
$services = $app->getServices();
$services->set(
    'requestMatcher', 
    new ClassDefinition(
        'Fwk\\Core\\Components\\RequestMatcher\\RequestMatcher'
    ), 
    true
);

// execute
$app->run();