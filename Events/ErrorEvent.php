<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;

/**
 * This event is notified when an exception occurs while the application is
 * running. {@see Application::run()}
 * 
 * If you wish to prevent the exception to be thrown, you have to stop the event
 * using  $event->stop()
 */
class ErrorEvent extends CoreEvent
{
    /**
     * Constructor
     * 
     * @param \Exception  $exception
     * @param Application $app
     * @param Context     $context 
     * 
     * @return void
     */
    public function __construct(\Exception $exception, Application $app, 
        Context $context = null
    ) {
        parent::__construct(
            AppEvents::ERROR, 
            array('exception'  =>  $exception), 
            $app, 
            $context
        );
    }
    
    /**
     * Gets the Exception
     * 
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}