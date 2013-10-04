<?php
namespace Fwk\Core\Action;

use Fwk\Core\ContextAware;
use Fwk\Core\ServicesAware;
use Fwk\Core\Preparable;
use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\ActionProxy;
use Symfony\Component\HttpFoundation\Request;
use Fwk\Core\Accessor;

abstract class AbstractControllerActionProxy implements ActionProxy
{
    /**
     * Instanciates the controller class
     * 
     * @return mixed
     */
    abstract protected function instanciate(Application $app);
    
    /**
     * Populates action class according to request params
     *
     * @param mixed   $class   Action's class
     * @param Request $request Current request
     *
     * @return void
     */
    protected function populate($class, Request $request)
    {
        $accessor = new Accessor($class);
        $props    = $accessor->getAttributes();
        foreach($props as $key) {
            $value = $request->get($key, false);
            if(false !== $value) {
                $accessor->set($key, $value);
            }
        }
    }
    
    protected function populateCoreInterfaces($instance, Application $app, 
        Context $context
    ) {
        if ($instance instanceof ContextAware) {
            $instance->setContext($context);
        }
        
        if ($instance instanceof ServicesAware) {
            $instance->setServices($app->getServices());
        }
        
        if ($instance instanceof Preparable) {
            call_user_func(array($instance, Preparable::PREPARE_METHOD));
        }
    }
    
    public function execute(Application $app, Context $context)
    {
        $instance = $this->instanciate($app);
        
        $this->populate($instance, $context->getRequest());
        $this->populateCoreInterfaces($instance, $app, $context);
        
        return call_user_func(array($instance, $this->method));
    }
}