<?php

namespace Fwk\Core;

use Fwk\Core\Action\ProxyFactory;
use Symfony\Component\HttpFoundation\Request;
use Fwk\Di\ClassDefinition;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fwk\Core\Preparable;

class TestController
{
    public function show()
    {
        return 'testController';
    }
}

// hello controller
class HelloController extends Action\Controller implements Preparable
{
    public $name;
    public $prepared = false;
    public function prepare() {
        $this->prepared = true;
    }
    public function show()
    {
        return 'Hello '. (empty($this->name) ? 'World' : $this->name);
    }
}

/**
 * Test class for Accessor.
 * Generated by PHPUnit on 2012-05-27 at 17:46:42.
 */
class BasicRuntimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected $object;


    /**
     */
    protected function setUp()
    {
        $this->object = Application::factory('testApp')
        ->addListener(new Components\RequestMatcher\RequestMatcherListener('requestMatcher'))
        ->register('TestSimpleClosureAction', ProxyFactory::factory(function() {
             return "test";
        }))
        ->register('TestClosureWithParams', ProxyFactory::factory(function($name) {
             return "hello ". $name;
        }))
        ->register('TestClosureResponse', ProxyFactory::factory(function() {
            return new RedirectResponse('http://www.example.org');
        }))
        ->register('TestSimpleController', ProxyFactory::factory('Fwk\\Core\\TestController:show'))
        ->register('TestHelloController', ProxyFactory::factory('Fwk\\Core\\HelloController:show'))
        ->register('TestServiceClosure', ProxyFactory::factory('@actionService'))
        ->register('TestServiceController', ProxyFactory::factory('@helloController:show'))
        ->register('TestContextAwareClosure', ProxyFactory::factory(function($context) {
             return $context;
        }))
        ->register('TestServicesAwareClosure', ProxyFactory::factory(function($services) {
             return $services;
        }))
        ;
        
        $this->object->getServices()->set(
            'requestMatcher', 
            new ClassDefinition('Fwk\\Core\\Components\\RequestMatcher\\RequestMatcher'), 
            true
        );
        
        $this->object->getServices()->set(
            'actionService', 
            function() {
                return "testService";
            }, 
            true
        );
            
        $this->object->getServices()->set(
            'helloController', 
            new ClassDefinition('Fwk\\Core\\HelloController'), 
            true
        );
    }

    protected function tearDown()
    {
        unset($this->object);
    }
    
    public function testSimpleClosureAction()
    {
        $request = Request::create('/TestSimpleClosureAction.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("test", $response->getContent());
    }
    
    public function testActionNotFound()
    {
        $request = Request::create('/Unknown.action');
        $this->setExpectedException('Fwk\Core\Exception');
        $this->object->run($request);
    }
    
    public function testNoActionFound()
    {
        $request = Request::create('/thisIsIncorrect');
        $this->setExpectedException('Fwk\Core\Exception');
        $this->object->run($request);
    }
    
    public function testDefaultAction()
    {
        $this->object->setDefaultAction('TestContextAwareClosure');
        $this->assertEquals('TestContextAwareClosure', $this->object->getDefaultAction());
        $request = Request::create('');
        $resp = $this->object->run($request);
        $this->assertInstanceOf('Fwk\Core\Context', $resp);
    }
    
    public function testDefaultRequest()
    {
        $this->object->setDefaultAction('TestContextAwareClosure');
        $this->assertEquals('TestContextAwareClosure', $this->object->getDefaultAction());
        $resp = $this->object->run();
        $this->assertInstanceOf('Fwk\Core\Context', $resp);
    }
    
    public function testClosureWithParamsAction()
    {
        $request = Request::create('/TestClosureWithParams.action?name=test');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("hello test", $response->getContent());
    }
    
    public function testClosureDirectResponse()
    {
        $request = Request::create('/TestClosureResponse.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
    }
    
    public function testSimpleController()
    {
        $request = Request::create('/TestSimpleController.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("testController", $response->getContent());
    }
    
    public function testHelloController()
    {
        $request = Request::create('/TestHelloController.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("Hello World", $response->getContent());
    }
    
    public function testHelloControllerWithParams()
    {
        $request = Request::create('/TestHelloController.action?name=Joe');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("Hello Joe", $response->getContent());
    }
    
    public function testServiceClosure()
    {
        $request = Request::create('/TestServiceClosure.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("testService", $response->getContent());
    }
    
    public function testServiceController()
    {
        $request = Request::create('/TestServiceController.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("Hello World", $response->getContent());
    }
    
    public function testServiceControllerWithParams()
    {
        $request = Request::create('/TestServiceController.action?name=Joe');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals("Hello Joe", $response->getContent());
    }
    
    public function testContextAwareClosure()
    {
        $request = Request::create('/TestContextAwareClosure.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Fwk\Core\Context', $response);
    }
    
    public function testServicesAwareClosure()
    {
        $request = Request::create('/TestServicesAwareClosure.action');
        $response = $this->object->run($request);
        $this->assertInstanceOf('Fwk\Di\Container', $response);
    }
    
    public function testDirectResponse()
    {
        $rsp = new \Symfony\Component\HttpFoundation\Response();
        $this->object->register('TestDirectResponse', ProxyFactory::factory(function() use ($rsp) { 
            return $rsp;
        }));
        $request = Request::create('/TestDirectResponse.action');
        $response = $this->object->run($request);
        $this->assertEquals($rsp, $response);
        
    }
}
