<?php
namespace Fwk\Core;

use Fwk\Core\Action\ControllerActionProxy;
use Fwk\Core\Action\CallableActionProxy;
use Fwk\Di\ClassDefinition;
use Fwk\Core\Context;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once __DIR__ .'/vendor/autoload.php';

// test controller
class TestController
{
    public function show()
    {
        return 'coucou from controller';
    }
}

// app
$app = Application::factory("myApp")
->addListener(new Components\RequestMatcher\RequestMatcherListener('requestMatcher'))
->addListener(new Components\ErrorReporterListener(array(
    'application_root' => dirname(__FILE__),
    'display_line_numbers' => true,
    'server_name' => 'DEV',
    'ignore_folders' => array(
        dirname(__FILE__) . DIRECTORY_SEPARATOR . '/vendor',
        '/home/neiluj/www/framework'
    ),
    'enable_saving' => false,
    'catch_ajax_errors' => true,
    'snippet_num_lines' => 10
))) 
->addListener(new Components\SessionListener())
->register('TestClosure', new CallableActionProxy(function() {
    return "coucou from closure";
}))
->register('TestClosureResponse', new CallableActionProxy(function() {
    return new RedirectResponse('http://www.example.org');
}))
->register('TestInclude', new Action\IncludeActionProxy(__DIR__ . DIRECTORY_SEPARATOR . 'test_incl_proxy.php'))
->register('TestController', new ControllerActionProxy('Fwk\\Core\\TestController', 'show'))
->register('TestCtxClosure', new CallableActionProxy(function(Context $context) {
    return "coucou from ContextAware closure";
}))
->register('TestService', new Action\ServiceActionProxy('actionService'))
;

// services
$services = $app->getServices();
$services->set(
    'requestMatcher', 
    new ClassDefinition(
        'Fwk\\Core\\Components\\RequestMatcher\\RequestMatcher'
    ), 
    true
);
$services->set(
    'actionService', 
    function() {
        return "success from service";
    }, 
    true
);

// execute
$app->run();