<?php
namespace TestApp\actions;


use Fwk\Core\Preparable;

class Hello implements Preparable
{
    public $name = null;
          
    public function prepare()
    {
    }
    
    public function show()
    {
        return 'Hello '. $this->name;
    }
}