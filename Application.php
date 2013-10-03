<?php
namespace Fwk\Core;

use Fwk\Core\ActionProxy;
use Fwk\Core\Exceptions\InvalidAction;
use Fwk\Events\Dispatcher;
use Symfony\Component\HttpFoundation\Request;
use Fwk\Di\Container;
use Fwk\Core\Events\RequestEvent;
use Fwk\Core\Events\DispatchEvent;
use Fwk\Core\Events\BeforeActionEvent;
use Fwk\Core\Events\AfterActionEvent;
use Fwk\Core\Events\EndEvent;
use Fwk\Core\Events\BootEvent;
use Fwk\Core\Events\ErrorEvent;
use Fwk\Core\Events\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class Application extends Dispatcher implements \ArrayAccess
{
    /**
     * Application name
     * @var string
     */
    protected $id;
    
    /**
     * List of registered actions
     * @var array
     */
    protected $actions = array();
    
    /**
     * Services container (Di)
     * @var Container
     */
    protected $services;
    
    /**
     * Constructor
     * 
     * @param string    $id       Application name
     * @param Container $services Services Container (di)
     * 
     * @return void
     */
    public function __construct($id, Container $services = null)
    {
        $this->id       = $id;
        
        if (null === $services) {
            $services = new Container();
        }
        
        $this->services = $services;
    }
    
    /**
     * Registers an action 
     * 
     * @param string      $actionName Name of the action
     * @param ActionProxy $proxy      Proxy instance to the action
     * 
     * @return Application 
     */
    public function register($actionName, ActionProxy $proxy)
    {
        $this->actions[$actionName] = $proxy;
        
        return $this;
    }
    
    /**
     * Unregisters an action
     * 
     * @param string $actionName Name of the action
     * 
     * @return Application
     * @throws InvalidAction if action is not registered
     */
    public function unregister($actionName)
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new InvalidAction("$actionName is not a registered Action");
        }
        
        unset($this->actions[$actionName]);
        
        return $this;
    }
    
    /**
     * Returns the ActionProxy of a registered action
     * 
     * @param string $actionName name of the action
     * 
     * @return ActionProxy the proxy instance to the action
     * @throws InvalidAction if action is not registered
     */
    public function get($actionName)
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new InvalidAction("$actionName is not a registered Action");
        }
        
        return $this->actions[$actionName];
    }
    
    /**
     * Tells if an action is registered
     * 
     * @param string $actionName Name of the action
     * 
     * @return boolean
     */
    public function exists($actionName)
    {
        return array_key_exists($actionName, $this->actions);
    }
    
    /**
     * Returns the list (array) of all registered actions (keys) and their 
     * according ActionProxy (values)
     * 
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }
    
    /**
     * Instanciates a new Application 
     * (useful for chaining)
     * 
     * @param string $id Application name
     * 
     * @return Application App instance
     */
    public static function factory($id)
    {
        return new self($id);
    }
    
    /**
     * Returns the Application name
     * 
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the Services Container
     * 
     * @return Container
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Defines a Services Container
     * 
     * @param Container $services Services Container (Di)
     * 
     * @return Application
     */
    public function setServices(Container $services)
    {
        $this->services = $services;
        
        return $this;
    }
    
    /**
     * Runs the Application for a defined (or new) HTTP request
     * 
     * @param Request $request The HTTP request (optional)
     * 
     * @return void
     */
    public function run(Request $request = null)
    {
        $this->notify(new BootEvent($this));
        
        if (null === $request) {
           $request = Request::createFromGlobals();
        }
        
        $context = new Context($request);
        
        try {
            $this->notify(new RequestEvent($request, $this, $context));

            if (!$context->isReady()) {
                $this->notify(new DispatchEvent($this, $context));
            }

            if (!$context->isReady()) {
                throw new Exceptions\InvalidAction('No action found');
            }

            if (!$this->exists($context->getActionName())) {
                throw new Exceptions\InvalidAction('Unregistered action "'. $context->getActionName() .'"');
            }

            $proxy = $this->get($context->getActionName());
            $this->notify(new BeforeActionEvent($proxy, $this, $context));

            $result = $proxy->execute($this, $context);
            $context->setResult($result);
            
            $this->notify(new AfterActionEvent($proxy, $this, $context));
            
            if (!$context->isDone()) {
                if ($result instanceof Response) {
                    $response = $result;
                } else {
                    $response = new Response($result);
                }
                $context->setResponse($response);
            } else {
                $response = $context->getResponse();
            }
            
            $this->notify(new ResponseEvent($response, $this, $context));
        } catch(\Exception $exp) {
            $event = new ErrorEvent($exp, $this, $context);
            $this->notify($event);
            
            if (!$event->isStopped()) {
                throw $exp;
            }
        }
        
        $this->notify(new EndEvent($this, $context));
        if ($context->getResponse() instanceof Response) {
            $context->getResponse()->send();
        }
    }
    
    public function offsetExists($actionName)
    {
        return $this->exists($actionName);
    }
    
    public function offsetGet($actionName)
    {
        return $this->get($actionName);
    }
    
    public function offsetSet($actionName, $proxy)
    {
        return $this->register($actionName, $proxy);
    }
    
    public function offsetUnset($actionName)
    {
        return $this->unregister($actionName);
    }
}