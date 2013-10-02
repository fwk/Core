<?php
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;

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
    
    public function execute() {
        ;
    }
}
