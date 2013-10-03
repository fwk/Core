<?php
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;
use Fwk\Core\ContextAware;
use Fwk\Core\ServicesAware;
use Fwk\Core\Preparable;
use Fwk\Core\Application;
use Fwk\Core\Context;

class ServiceControllerActionProxy implements ActionProxy
{
    protected $serviceName;
    
    protected $method;

    public function __construct($serviceName, $method)
    {
        if (empty($serviceName) || empty($method)) {
            throw new \InvalidArgumentException("Controller service name and method cannot be empty");
        }
        
        $this->serviceName  = $serviceName;
        $this->method       = $method;
    }
    
    public function execute(Application $app, Context $context)
    {
        $instance = $app->getServices()->get($this->serviceName);
        
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
