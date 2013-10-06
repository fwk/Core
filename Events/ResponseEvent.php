<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;
use Symfony\Component\HttpFoundation\Response;

/**
 */
class ResponseEvent extends CoreEvent
{
    protected $response;
    
    /**
     * Constructor
     * 
     * @param Response    $response
     * @param Application $app
     * @param Context     $context 
     * 
     * @return void
     */
    public function __construct(Response $response, Application $app, 
        Context $context = null
    ) {
        parent::__construct(
            AppEvents::RESPONSE, 
            array('response'  =>  $response), 
            $app, 
            $context
        );
    }
    
    public function getResponse()
    {
        return $this->response;
    }
}