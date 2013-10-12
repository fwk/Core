<?php

namespace Fwk\Core\Components\ResultType;

use Fwk\Di\ClassDefinition;
use Symfony\Component\HttpFoundation\Request;

class TestController {
    public function show()
    {
        return 'success';
    }
}

/**
 * Test class for ResultTypeService.
 * Generated by PHPUnit on 2013-10-07 at 00:23:59.
 */
class ResultTypeDescriptorAppTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Fwk\Core\Application
     */
    protected $app;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $service = new ResultTypeService();
        
        $services = new \Fwk\Di\Container();
        $services->set(
            'requestMatcher', 
            new ClassDefinition(
                'Fwk\\Core\\Components\\RequestMatcher\\RequestMatcher'
            ), 
            true
        );
        $services->set('viewHelper', new \Fwk\Core\Components\ViewHelper\ViewHelperService(), true);
        $services->set('resultTypeService', $service, true);
        
        $desc = new \Fwk\Core\Components\Descriptor\Descriptor(
                TEST_RESOURCES_DIR . DIRECTORY_SEPARATOR . 'Descriptor' .
                DIRECTORY_SEPARATOR . 'app_test.xml'
        );
        $desc->iniProperties(TEST_RESOURCES_DIR . DIRECTORY_SEPARATOR . 'Descriptor' .
                DIRECTORY_SEPARATOR . 'config-one.ini');
        
        $this->app = $desc->execute('testApp', $services);
    }
    
    public function testJsonResultType()
    {
        $req = Request::create('/Home.action?result=success');
        $result = $this->app->run($req);
        
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $result);
    }
}
