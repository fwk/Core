<?php
namespace Fwk\Core\Action;


use Fwk\Core\ActionProxy;
use Fwk\Core\Application;
use Fwk\Core\Context;

class ServiceActionProxy implements ActionProxy
{
    protected $serviceName;
    
    public function __construct($serviceName)
    {
        if (empty($serviceName)) {
            throw new \InvalidArgumentException("You must specify a Service Name");
        }
        
        $this->serviceName = $serviceName;
    }
    
    public function execute(Application $app, Context $context)
    {
        return $app->getServices()->get($this->serviceName);
    }
}
