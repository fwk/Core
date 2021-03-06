<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;
use Fwk\Core\ActionProxy;

class BeforeActionEvent extends CoreEvent
{
    public function __construct(ActionProxy $actionProxy, 
        Application $app = null, Context $context = null
    ) {
        parent::__construct(
            AppEvents::BEFORE_ACTION, 
            array(
                'actionProxy' => $actionProxy
            ), 
            $app, 
            $context
        );
    }
    
    /**
     * 
     * @return ActionProxy
     */
    public function getActionProxy()
    {
        return $this->actionProxy;
    }
}