<?php
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;
use Fwk\Core\Application;
use Fwk\Core\Context;

class CallableActionProxy implements ActionProxy
{
    const PARAM_CONTEXT_NAME = 'context';
    const PARAM_SERVICES_NAME = 'services';
    
    protected $callable;
    
    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("The closure is not callable");
        }
        
        $this->callable = $callable;
    }
    
    public function execute(Application $app, Context $context)
    {
        if ($this->callable instanceof \Closure) {
            $refFunc = new \ReflectionFunction($this->callable);

            $params = array();
            foreach ($refFunc->getParameters() as $param) {
                if ($param->getName() == self::PARAM_CONTEXT_NAME) {
                    $params[] = $context;
                }
                elseif ($param->getName() == self::PARAM_SERVICES_NAME) {
                    $params[] = $app->getServices();
                } 
            }
            
            $result = call_user_func_array($this->callable, $params);
        } else {
            $result = call_user_func($this->callable);
        }
        
        return $result;
    }
}