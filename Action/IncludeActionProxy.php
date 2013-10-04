<?php
namespace Fwk\Core\Action;


use Fwk\Core\ActionProxy;
use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\Exception;
use Fwk\Di\Container;

class IncludeActionProxy implements ActionProxy
{
    protected $file;
    
    protected $services;
    protected $context;
    
    public function __construct($file)
    {
        if (empty($file)) {
            throw new \InvalidArgumentException("You must specify a file to include");
        }
        
        $this->file    = $file;
    }
    
    public function execute(Application $app, Context $context)
    {
        if (!is_file($this->file)) {
            throw new Exception('Unable to include file: '. $this->file . ' (not found)');
        } elseif (!is_readable($this->file)) {
            throw new Exception('Unable to include file: '. $this->file . ' (not readable)');
        }
        
        $this->context = $context;
        $this->services = $app->getServices();
        
        return include $this->file;
    }
    
    /**
     * 
     * @return Container
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * 
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }
}
