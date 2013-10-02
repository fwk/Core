<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;
use Symfony\Component\HttpFoundation\Request;

class RequestEvent extends CoreEvent
{
    public function __construct(Request $request, Application $app = null, 
        Context $context = null
    ) {
        parent::__construct(
            AppEvents::REQUEST, 
            array('request'  =>  $request), 
            $app, 
            $context
        );
    }
    
    /**
     * Gets the Request instance
     * 
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Defines the Request 
     * 
     * @param Request $request Request instance
     * 
     * @return RequestEvent 
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        
        return $this;
    }
}