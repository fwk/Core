<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;

class BootEvent extends CoreEvent
{
    public function __construct(Application $app, Application $parentApp = null, 
        Context $context = null
    ) {
        parent::__construct(
            AppEvents::BOOT, 
            array('parentApplication'  =>  $parentApp), 
            $app, 
            $context
        );
        
        $this->parentApplication = $parentApp;
    }
    
    /**
     * Returns parent Application (if any) or null
     * 
     * @return Application
     */
    public function getParentApplication()
    {
        return $this->parentApplication;
    }

    /**
     * Defines the parent Application (if any)
     * 
     * @param Application $parentApplication
     * 
     * @return BootEvent 
     */
    public function setParentApplication(Application $parentApplication)
    {
        $this->parentApplication = $parentApplication;
        
        return $this;
    }
}