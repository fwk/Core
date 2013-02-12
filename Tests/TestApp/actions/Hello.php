<?php
namespace TestApp\actions;


use Fwk\Core\Preparable, 
    Fwk\Core\Action\Result;

class Hello implements \Fwk\Core\ServicesAware
{
    public $name = null;
          
    protected $services;
    
    public function show()
    {
        return Result::SUCCESS;
    }
    
    public function getServices() {
        return $this->services;
    }
    
    public function setServices($container) {
        $this->services = $container;
    }
}