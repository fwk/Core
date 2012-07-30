<?php
namespace TestApp\actions;


use Fwk\Core\Preparable;

class Hello implements Preparable
{
    public function prepare()
    {
        echo 'prepare!!';
    }
    
    public function show()
    {
        return 'success';
    }
}