<?php
namespace Fwk\Core;

use Fwk\Di\ClassDefinition;
use Fwk\Core\Context;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fwk\Core\Action\ProxyFactory;

require_once __DIR__ .'/vendor/autoload.php';

// test controller
class TestController
{
    public function show()
    {
        return 'coucou from controller';
    }
}

// hello controller
class HelloController
{
    public $name;
    public function show()
    {
        return 'Hello (controller) '. (empty($this->name) ? 'World' : $this->name);
    }
}

// app
$app = Application::factory("myApp")
->addListener(new Components\RequestMatcher\RequestMatcherListener('requestMatcher'))
->addListener(new Components\ErrorReporterListener(array(
    'application_root' => dirname(__FILE__),
    'display_line_numbers' => true,
    'server_name' => 'DEV',
    'ignore_folders' => array(),
    'enable_saving' => false,
    'catch_ajax_errors' => true,
    'snippet_num_lines' => 10
))) 
->addListener(new Components\SessionListener())
->addListener(new Components\ResultType\ResultTypeListener('resultTypeService'))
->register('TestClosure', ProxyFactory::factory(function() {
    return "coucou from closure";
}))
->register('Hello', ProxyFactory::factory(function($name = null) {
    return "Hello ". (empty($name) ? 'World' : $name);
}))
->register('TestClosureResponse', ProxyFactory::factory(function() {
    return new RedirectResponse('http://www.example.org');
}))
->register('TestInclude', ProxyFactory::factory('+'. __DIR__ . DIRECTORY_SEPARATOR . 'test_incl_proxy.php'))
->register('TestController', ProxyFactory::factory('Fwk\\Core\\TestController:show'))
->register('HelloController', ProxyFactory::factory('Fwk\\Core\\HelloController:show'))
->register('TestCtxClosure', ProxyFactory::factory(function(Context $context) {
    return "coucou from ContextAware closure";
}))
->register('TestService', ProxyFactory::factory('@actionService'))
->setDefaultAction('TestClosure')
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
$services->set(
    'resultTypeService', 
    new ClassDefinition(
        'Fwk\\Core\\Components\\ResultType\\ResultTypeService'
    ), 
    true
);
// execute
$response = $app->run();
if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
    $response->send();
} else {
    echo $response;
}