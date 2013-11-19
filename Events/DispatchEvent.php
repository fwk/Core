<?php
namespace Fwk\Core\Events;

use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\CoreEvent;
use Fwk\Core\AppEvents;

class DispatchEvent extends CoreEvent
{
    public function __construct(Application $app = null, 
        Context $context = null
    ) {
        parent::__construct(
            AppEvents::DISPATCH, 
            array(), 
            $app, 
            $context
        );
    }
}