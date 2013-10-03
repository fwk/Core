<?php
namespace Fwk\Core\Components\RequestMatcher;

use Fwk\Core\Events\RequestEvent;

class RequestMatcherListener
{
    protected $serviceName;
    
    public function __construct($serviceName)
    {
        $this->serviceName = $serviceName;
    }
    
    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $matcher = $event->getApplication()->getServices()->get($this->serviceName);
        
        $actionName = $matcher->match($request);
        
        $event->getContext()->setActionName($actionName);
    }
}