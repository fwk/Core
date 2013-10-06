<?php
namespace Fwk\Core\Components\ResultType;

use Fwk\Core\Events\AfterActionEvent;
use Symfony\Component\HttpFoundation\Response;

class ResultTypeListener
{
    protected $serviceName;
    
    public function __construct($serviceName)
    {
        $this->serviceName = $serviceName;
    }
    
    public function onAfterAction(AfterActionEvent $event)
    {
        $result     = $event->getContext()->getResult();
        if (!is_string($result)) {
            return;
        }
        
        $service = $event->getApplication()
                    ->getServices()
                    ->get($this->serviceName);
        
        try {
            $response = $service->execute($result, 
                $event->getContext(), 
                $event->getActionProxy()->getActionData()
            );
            
            if ($response instanceof Response) {
                $event->getContext()->setResponse($response);
            }
        } catch(Exception $exception) {
        }
    }
}