<?php
namespace Fwk\Core\Action;


use Fwk\Core\ActionProxy;

class IncludeActionProxy implements ActionProxy
{
    protected $file;
    
    public function __construct($file)
    {
        if (empty($file)) {
            throw new \InvalidArgumentException("You must specify a file to include");
        }
        
        $this->file    = $file;
    }
    
    public function execute() {
        ;
    }
}
