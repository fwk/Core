<?php
namespace Fwk\Core\Components;

use Fwk\Core\Events\BootEvent;
use Fwk\Core\Events\RequestEvent;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionListener
{
    protected $serviceName;
    protected $storageServiceName;
    
    public function __construct($sessionServiceName = 'session', 
        $storageServiceName = null
    ) {
        $this->serviceName = $sessionServiceName;
        $this->storageServiceName = $storageServiceName;
    }
    
    public function onBoot(BootEvent $event)
    {
        if (!empty($this->storageServiceName)) {
            $storage = $event->getApplication()->getServices()->get($this->storageServiceName);
        } else {
            $storage = null;
        }
        
        $session = new Session($storage);
        $event->getApplication()->getServices()->set($this->serviceName, $session, true);
    }
    
    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $session = $event->getApplication()->getServices()->get($this->serviceName);
        $request->setSession($session);
        
        $session->start();
    }
}