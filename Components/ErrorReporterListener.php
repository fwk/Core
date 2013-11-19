<?php
namespace Fwk\Core\Components;

use Fwk\Core\Events\ErrorEvent;

class ErrorReporterListener
{
    protected $options = array();
    
    public function __construct(array $handlerOptions = array())
    {
        $this->options = $handlerOptions;
    }
    
    public function onError(ErrorEvent $event)
    {
        header('X-Error-Message: '. $event->getException()->getMessage(), true, 500);
        $handler = new \php_error\ErrorHandler($this->options);
        $handler->turnOn();
    }
}