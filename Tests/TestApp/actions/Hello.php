<?php
namespace TestApp\actions;


use Fwk\Core\Preparable, Fwk\Core\Action\Result;

class Hello implements Preparable
{
    public $name = null;
          
    public function prepare()
    {
    }
    
    public function show()
    {
        return Result::SUCCESS;
    }
}