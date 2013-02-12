<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;

class EndEvent extends CoreEvent
{
    public function __construct($actionResult, Application $app = null, 
        Context $context = null
    ) {
        parent::__construct(
            AppEvents::REQUEST, 
            array('result'  =>  $result), 
            $app, 
            $context
        );
    }
    
    /**
     * Gets the Action's result
     * 
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Defines the Result
     * 
     * @param mixed $result Action's result
     * 
     * @return EndEvent 
     */
    public function setRequest($result)
    {
        $this->result = $result;
        
        return $this;
    }
}