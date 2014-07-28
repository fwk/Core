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
     * @param string    $name     Application name
     * @param Container $services Services Container
     *
     * @return Application App instance
     */
    public static function factory($name, Container $services = null)
    {
        return new self($name, $services);
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

            $result = $this->runAction($context);

            if (!$context->isDone()) {
                if ($result instanceof Response) {
                    $context->setResponse($result);
                } elseif (is_string($result)) {
                    $context->setResponse(new Response($result));
                }
            }

            if ($context->getResponse() instanceof Response) {
                $this->notify(
                    new ResponseEvent(
                        $context->getResponse(),
                        $this,
                        $context
                    )
                );
            }
        } catch(\Exception $exp) {
            $event = new ErrorEvent($exp, $this, $context);
            $this->notify($event);

            if (!$event->isStopped()) {
                throw $exp;
            } else {
                return null;
            }
        }

        $endEvent = new EndEvent($this, $context);
        $this->notify($endEvent);

        if ($endEvent->isStopped()) {
            return null;
        }

        if ($context->getResponse() instanceof Response) {
            return $context->getResponse();
        }

        return $context->getResult();
    }

    /**
     *
     * @param Context $context
     *
     * @return mixed
     */
    public function runAction(Context $context)
    {
        if (!$context->isReady()) {
            throw new Exception('Context is not ready (i.e. no action defined)');
        }

        if (!$this->exists($context->getActionName())) {
            throw new Exception('Unregistered action "'. $context->getActionName() .'"');
        }

        $proxy = $this->get($context->getActionName());
        $this->notify(new BeforeActionEvent($proxy, $this, $context));
        if ($context->isDone()) {
            return $context->getResult();
        }
        $result = $proxy->execute($this, $context);
        $context->setResult($result);
        $this->notify(new AfterActionEvent($proxy, $this, $context));

        return $result;
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