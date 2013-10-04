<?php
namespace Fwk\Core\Action;

use Fwk\Core\Application;

class ControllerActionProxy extends AbstractControllerActionProxy 
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
    
    protected function instanciate(Application $app)
    {
        $refClass = new \ReflectionClass($this->className);
        
        return $refClass->newInstanceArgs();
    }
}
