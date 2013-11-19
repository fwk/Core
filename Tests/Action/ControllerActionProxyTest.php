<?php

namespace Fwk\Core\Action;


/**
 * Test class for ControllerActionProxy.
 * Generated by PHPUnit on 2013-10-06 at 20:58:29.
 */
class ControllerActionProxyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ControllerActionProxy
     */
    protected $object;

    public function testEmptyControllerMethodException()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->object = new ControllerActionProxy('test', '');
    }

}
