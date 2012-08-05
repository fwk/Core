<?php
namespace TestApp\actions;


use Fwk\Core\Preparable, 
    Fwk\Core\Action\Result;

class Hello
{
    public $name = null;
          
    public function show()
    {
        return Result::SUCCESS;
    }
}