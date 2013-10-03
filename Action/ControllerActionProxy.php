<?php
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;
use Fwk\Core\ContextAware;
use Fwk\Core\ServicesAware;
use Fwk\Core\Preparable;
use Fwk\Core\Application;
use Fwk\Core\Context;

class ControllerActionProxy implements ActionProxy
{
    protected $className;
    
    protected $method;

    public function __construct($className, $method)
    {
        if (empty($className) || empty($method)) {
            throw new \InvalidArgumentException("Controller class name and method cannot be empty");
        }
        
        $this->className    = $className;
        $this->method       = $method;
    }
    
    public function execute(Application $app, Context $context)
    {
        $refClass = new \ReflectionClass($this->className);
        $instance = $refClass->newInstanceArgs();
        
        if ($instance instanceof ContextAware) {
            $instance->setContext($context);
        }
        
        if ($instance instanceof ServicesAware) {
            $instance->setServices($app->getServices());
        }
        
        if ($instance instanceof Preparable) {
            call_user_func(array($instance, Preparable::PREPARE_METHOD));
        }
        
        return call_user_func(array($instance, $this->method));
    }
}
