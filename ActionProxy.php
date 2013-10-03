<?php
namespace Fwk\Core;

use Fwk\Core\Context;
use Fwk\Core\Application;

interface ActionProxy
{
    public function execute(Application $app, Context $context);
}