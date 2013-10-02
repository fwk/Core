<?php
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;

class CallableActionProxy implements ActionProxy
{
    protected $callable;
    
    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("The closure is not callable");
        }
        
        $this->callable = $callable;
    }
    
    public function execute() {
        ;
    }
}
