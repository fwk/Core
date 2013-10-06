<?php
namespace Fwk\Core;

use Fwk\Core\ActionProxy;
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
    protected $name;
    
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
     * The default action (i.e the "homepage")
     * @var string
     */
    protected $defaultAction;
    
    /**
     * Constructor
     * 
     * @param string    $name     Application name
     * @param Container $services Services Container (di)
     * 
     * @return void
     */
    public function __construct($name, Container $services = null)
    {
        $this->name = $name;
        
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
     * @throws Exception if action is not registered
     */
    public function unregister($actionName)
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new Exception("$actionName is not a registered Action");
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
     * @throws Exception if action is not registered
     */
    public function get($actionName)
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new Exception("$actionName is not a registered Action");
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
     * @param string $name Application name
     * 
     * @return Application App instance
     */
    public static function factory($name)
    {
        return new self($name);
    }
    
    /**
     * Returns the Application name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @return mixed
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
                if (null === $this->defaultAction || $context->isError()) {
                    throw new Exception('No action found');
                }
                $context->setActionName($this->defaultAction);
            }

            if (!$this->exists($context->getActionName())) {
                throw new Exception('Unregistered action "'. $context->getActionName() .'"');
            }

            $proxy = $this->get($context->getActionName());
            $this->notify(new BeforeActionEvent($proxy, $this, $context));

            $result = $response = $proxy->execute($this, $context);
            $context->setResult($result);
            
            $this->notify(new AfterActionEvent($proxy, $this, $context));
            
            $response = null;
            if (!$context->isDone()) {
                if ($result instanceof Response) {
                    $response = $result;
                    $context->setResponse($response);
                } elseif (is_string($result)) {
                    $response = new Response($result);
                    $context->setResponse($response);
                }
            }
            
            if ($response instanceof Response) {
                $this->notify(new ResponseEvent($response, $this, $context));
            }
        } catch(\Exception $exp) {
            $event = new ErrorEvent($exp, $this, $context);
            $this->notify($event);
            
            if (!$event->isStopped()) {
                throw $exp;
            }
        }
        
        $this->notify(new EndEvent($this, $context));
        if ($context->getResponse() instanceof Response) {
            return $response;
        } 
        
        return $context->getResult();
    }
    
    /**
     * Returns the default action name (if any)
     * 
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    /**
     * Defines a default action. Basically the one which answers on /
     * 
     * @param string $defaultAction Default action name
     * 
     * @return Application 
     */
    public function setDefaultAction($defaultAction)
    {
        $this->defaultAction = $defaultAction;
        
        return $this;
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
        if (!$proxy instanceof ActionProxy) {
            $proxy = Action\ProxyFactory::factory($proxy);
        }
        
        return $this->register($actionName, $proxy);
    }
    
    public function offsetUnset($actionName)
    {
        return $this->unregister($actionName);
    }
}