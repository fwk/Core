<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;
use Fwk\Core\ActionProxy;

class AfterActionEvent extends CoreEvent
{
    public function __construct(ActionProxy $actionProxy,
        Application $app = null, Context $context = null
    ) {
        parent::__construct(
            AppEvents::AFTER_ACTION, 
            array(
                'actionProxy'   => $actionProxy
            ), 
            $app, 
            $context
        );
    }
    
    public function getActionProxy()
    {
        return $this->actionProxy;
    }

    public function setActionProxy(ActionProxy $actionProxy)
    {
        $this->actionProxy = $actionProxy;
    }
}