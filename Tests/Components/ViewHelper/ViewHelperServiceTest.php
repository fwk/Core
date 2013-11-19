<?php

namespace Fwk\Core\Components\ViewHelper;

class TestHelper extends AbstractViewHelper implements ViewHelper
{
    public function execute(array $arguments)
    {
        return 'testHelper';
    }
}

class TestHelperFail extends AbstractViewHelper implements ViewHelper
{
    public function execute(array $arguments)
    {
        throw new \Exception('FAIL'); 
    }
}

/**
 */
class ViewHelperServiceTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ViewHelperService
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new ViewHelperService();
    }

    
    public function testViewHelperDefaults()
    {
        $this->assertEquals(true, $this->object->isThrowExceptions());
        $this->assertEquals(ViewHelperService::DEFAULT_PROP_NAME, $this->object->getPropName());
        $this->assertEquals($this->object, $this->object->setPropName('testProp'));
        $this->assertEquals('testProp', $this->object->getPropName());
        $this->assertEquals($this->object, $this->object->throwExceptions(false));
        $this->assertEquals(false, $this->object->isThrowExceptions());
    }
    
    public function testViewHelperRegistration()
    {
        $this->assertEquals($this->object, $this->object->add('test', new TestHelper()));
        $this->assertInstanceOf('Fwk\Core\Components\ViewHelper\TestHelper', $this->object->helper('test'));
        $this->assertEquals($this->object, $this->object->remove('test'));
        
        $this->setExpectedException('Fwk\Core\Components\ViewHelper\Exception');
        $this->object->remove('test');
    }
    
    public function testViewHelperMultiRegistration()
    {
        $this->assertEquals($this->object, $this->object->addAll(array('test' => new TestHelper())));
        $this->assertInstanceOf('Fwk\Core\Components\ViewHelper\TestHelper', $this->object->helper('test'));
    }
    
    public function testViewHelperNotFound()
    {
        $this->setExpectedException('Fwk\Core\Components\ViewHelper\Exception');
        $this->object->helper('test');
    }
    
    public function testViewHelperFailException()
    {
        $this->assertEquals($this->object, $this->object->add('fail', new TestHelperFail()));
        $this->setExpectedException('Fwk\Core\Components\ViewHelper\Exception');
        $this->object->__call('fail', array());
    }
    
    public function testViewHelperFailSilently()
    {
        $this->assertEquals($this->object, $this->object->add('fail', new TestHelperFail()));
        $this->object->throwExceptions(false);
        $this->assertFalse($this->object->__call('fail', array()));
    }
    
    public function testViewHelperNotFoundSilent()
    {
        $this->object->throwExceptions(false);
        $this->assertFalse($this->object->__call('fail', array()));
    }
    
    public function testViewHelperNotFoundException()
    {
        $this->setExpectedException('Fwk\Core\Components\ViewHelper\Exception');
        $this->object->__call('fail', array());
    }
}
