<?php
namespace Fwk\Core\Action;

use Fwk\Core\Application;

class ServiceControllerActionProxy extends AbstractControllerActionProxy 
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
    
    /**
     * Instanciates the controller class
     * 
     * @return mixed
     */
    protected function instantiate(Application $app)
    {
        return $app->getServices()->get($this->serviceName);
    }
}
