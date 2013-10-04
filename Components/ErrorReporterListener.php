<?php
namespace Fwk\Core\Components;

use Fwk\Core\Events\BootEvent;

class ErrorReporterListener
{
    protected $options = array();
    
    public function __construct(array $handlerOptions = array())
    {
        $this->options = $handlerOptions;
    }
    
    public function onBoot()
    {
        $handler = new \php_error\ErrorHandler($this->options);
        $handler->turnOn();
    }
}