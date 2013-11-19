<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;

class BootEvent extends CoreEvent
{
    public function __construct(Application $app, Context $context = null)
    {
        parent::__construct(
            AppEvents::BOOT, 
            array(), 
            $app, 
            $context
        );
    }
}