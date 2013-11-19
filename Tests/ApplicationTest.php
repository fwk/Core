<?php

namespace Fwk\Core;


/**
 * Test class for Application.
 * Generated by PHPUnit on 2013-10-03 at 17:24:19.
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Application
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new Application('testApp');
    }

    /**
     * @covers Fwk\Core\Application::offsetSet
     * @covers Fwk\Core\Application::offsetExists
     * @covers Fwk\Core\Application::exists
     */
    public function testRegister() {
        $this->assertFalse($this->object->exists('TestAction'));
        $this->assertEquals($this->object, $this->object->register('TestAction', new Action\CallableActionProxy(function() { return 'test'; })));
        $this->assertTrue($this->object->exists('TestAction'));
    }

    /**
     * @covers Fwk\Core\Application::offsetUnset
     */
    public function testUnregister() {
        $this->assertEquals($this->object, $this->object->register('TestAction', new Action\CallableActionProxy(function() { return 'test'; })));
        $this->assertTrue($this->object->exists('TestAction'));
        $this->assertEquals($this->object, $this->object->unregister('TestAction'));
        $this->assertFalse($this->object->exists('TestAction'));
    }

    /**
     */
    public function testUnregisterInvalidAction() {
        $this->setExpectedException('Fwk\Core\Exception');
        $this->object->unregister('TestAction');
    }
    
    /**
     * @covers Fwk\Core\Application::offsetGet
     */
    public function testGet() {
        $proxy = new Action\CallableActionProxy(function() { return 'test'; });
        $this->assertEquals($this->object, $this->object->register('TestAction', $proxy));
        $this->assertEquals($proxy, $this->object->get('TestAction'));
    }
    
    /**
     */
    public function testGetInvalidAction() {
        $this->setExpectedException('Fwk\Core\Exception');
        $this->object->get('TestAction');
    }

    /**
     */
    public function testGetActions() {
        $this->assertEquals($this->object, $this->object->register('TestAction', new Action\CallableActionProxy(function() { return 'test'; })));
        $this->assertEquals(1, count($this->object->getActions()));
    }

    /**
     */
    public function testGetId() {
        $this->assertEquals('testApp', $this->object->getName());
    }

    /**
     * @covers Fwk\Core\Application::getServices
     * @covers Fwk\Core\Application::setServices
     * @todo Implement testGetServices().
     */
    public function testGetSetServices() {
        $container = new \Fwk\Di\Container();
        $this->assertEquals($this->object, $this->object->setServices($container));
        $this->assertEquals($container, $this->object->getServices());
    }
    
    public function testMagicMethods()
    {
        $this->assertFalse(isset($this->object['TestAction']));
        $this->object['TestAction'] = function() { return 'closure controller'; };
        $this->assertTrue(isset($this->object['TestAction']));
        $this->assertInstanceOf('Fwk\Core\ActionProxy', $this->object['TestAction']);
        unset($this->object['TestAction']);
        $this->assertFalse(isset($this->object['TestAction']));
    }
}
