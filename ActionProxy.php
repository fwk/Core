<?php
namespace Fwk\Core;

use Fwk\Core\Context;
use Fwk\Core\Application;

interface ActionProxy
{
    /**
     * @return mixed
     */
    public function execute(Application $app, Context $context);
    
    /**
     * @return array
     */
    public function getActionData();
    
    /**
     * @param array $data
     * 
     * @return void
     */
    public function setActionData(array $data);
    
}