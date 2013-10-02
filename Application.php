<?php
namespace Fwk\Core;

use Fwk\Core\ActionProxy;
use Fwk\Core\Exceptions\InvalidAction;
use Fwk\Events\Dispatcher;
use Symfony\Component\HttpFoundation\Request;

class Application extends Dispatcher
{
    protected $id;
    
    protected $actions = array();
    
    public function __construct($id)
    {
        $this->id = $id;
    }
    
    /**
     * 
     * @param string       $actionName
     * @param ActionProxy  $proxy
     * 
     * @return Application 
     */
    public function register($actionName, ActionProxy $proxy)
    {
        $this->actions[$actionName] = $proxy;
        
        return $this;
    }
    
    public function unregister($actionName)
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new InvalidAction("$actionName is not a registered Action");
        }
        
        unset($this->actions[$actionName]);
        
        return $this;
    }
    
    public function get($actionName)
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new InvalidAction("$actionName is not a registered Action");
        }
        
        return $this->actions[$actionName];
    }
    
    public function exists($actionName)
    {
        return array_key_exists($actionName, $this->actions);
    }
    
    public function getAll()
    {
        return $this->actions;
    }
    
    /**
     * 
     * @param string $id
     * 
     * @return Application
     */
    public static function factory($id)
    {
        return new self($id);
    }
    
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }
    }
}