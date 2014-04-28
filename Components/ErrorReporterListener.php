<?php
namespace Fwk\Core\Components;

use Fwk\Core\Events\ErrorEvent;

class ErrorReporterListener
{
    public function onError(ErrorEvent $event)
    {
        if (php_sapi_name() == "cli") {
            return;
        }
        
        header('X-Error-Message: '. $event->getException()->getMessage(), true, 500);
        
        $whoops = new \Whoops\Run;
        
        $request = $event->getContext()->getRequest();
        $handler = new \Whoops\Handler\PrettyPageHandler;
        $event->getContext()->getActionName();
        $prev    = $event->getException()->getPrevious();
        
        $fwkTable = array(
            'Application name'  => $event->getApplication()->getName(),
            'Action name'       => $event->getContext()->getActionName(),
            'Context state'     => $event->getContext()->getState(),
            'Context error'     => $event->getContext()->getError(),
        );
        $parents = $this->getParentExceptionsMessage($event->getException());
        if (!is_array($parents)) {
            $parents = array();
        }
        
        foreach ($parents as $idx => $exp) {
            $fwkTable['Parent Exception #'. $idx] = $exp;
        }
        
        $handler->addDataTable('Fwk\Core Informations', $fwkTable);
        
        $handler->addDataTable('Request Informations', array(
            'URI'         => $request->getUri(),
            'Request URI' => $request->getRequestUri(),
            'Path Info'   => $request->getPathInfo(),
            'Query String'=> $request->getQueryString() ?: '<none>',
            'HTTP Method' => $request->getMethod(),
            'Script Name' => $request->getScriptName(),
            'Base Path'   => $request->getBasePath(),
            'Base URL'    => $request->getBaseUrl(),
            'Scheme'      => $request->getScheme(),
            'Port'        => $request->getPort(),
            'Host'        => $request->getHost(),
        ));
        
        $whoops->pushHandler($handler);
        $whoops->register();
    }
    
    protected function getParentExceptionsMessage(\Exception $exp) 
    {
        $message = array(); 
        while($exp->getPrevious() instanceof \Exception) {
            $exp = $exp->getPrevious();
            $message[] = get_class($exp) .": ". $exp->getMessage();
        }
        
        return (empty($message) ? '<none>' : $message);
    }
}