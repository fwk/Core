<?php
/**
 * Fwk
 *
 * Copyright (c) 2011-2012, Julien Ballestracci <julien@nitronet.org>.
 * All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP Version 5.3
 *
 * @category  Core
 * @package   Fwk\Core
 * @author    Julien Ballestracci <julien@nitronet.org>
 * @copyright 2011-2012 Julien Ballestracci <julien@nitronet.org>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://www.phpfwk.com
 */
namespace Fwk\Core;

use Fwk\Core\Object,
    Fwk\Core\ActionProxy, 
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response, 
    Fwk\Events\Event;

/**
 * Action Context
 *
 * This class represents the whole running context of a client's request. 
 * <pre>
 *          Client   -->    Request 
 *            :                |
 *            |     Context    |
 *            |                :
 *          Response   <--  Action
 * </pre>
 * 
 * @category   Core
 * @package    Fwk
 * @subpackage Core
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class Context extends Object
{
    const STATE_INIT        = 0;
    const STATE_READY       = 1;
    const STATE_ERROR       = 2;
    const STATE_EXECUTED    = 3;
    const STATE_DONE        = 4;

    /**
     * Client request
     * 
     * @var Request
     */
    protected $request;
    
    /**
     * Reponse to be sent to client
     * 
     * @var Response
     */
    protected $response;
    
    /**
     * Running Action's name
     * 
     * @var string
     */
    protected $actionName;
    
    /**
     * Current state
     * 
     * @var integer
     */
    protected $state = self::STATE_INIT;
    
    /**
     * Description of current error
     * 
     * @var string
     */
    protected $error;
    
    /**
     * Action Proxy
     * 
     * @var Proxy
     */
    protected $proxy;
    
    /**
     * Action's result returned by executed method
     * (recommended: string)
     * 
     * @var mixed
     */
    protected $result;

    /**
     * The parent context (if any)
     * 
     * @var Context
     */
    protected $parent;
    
    /**
     * Constructor
     * 
     * @param Request  $request  Client's request
     * @param Response $response Pre-defined return response
     * 
     * @return void
     */
    public function __construct(Request $request, Response $response = null)
    {
        $this->request      = $request;
        $this->response     = $response;
    }

    /**
     * Returns client's request
     * 
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns Response if defined or null otherwise
     * 
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    
    /**
     * Returns Action name
     * 
     * @return string 
     */
    public function getActionName()
    {
        return $this->action;
    }
    
    /**
     * Defines action name and toggle context state to READY
     * 
     * @param string $str Action name
     * 
     * @see ContextEvents::READY
     * @return void
     */
    public function setActionName($str)
    {
        $this->action   = $str;
        $this->state    = self::STATE_READY;
        unset($this->proxy);
        
        $this->notify(new Event(ContextEvents::READY));
    }

    
    /**
     * Returns parent context (if any)
     * 
     * @return Context
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Defines a parent Context 
     * 
     * This usually happends when running multiples actions within a same 
     * request (modules, widgets ...)
     * 
     * @param Context $context The parent context
     * 
     * @return void
     */
    public function setParent(Context $context)
    {
        $this->parent = $context;
    }

    /**
     * Tells if a parent context is defined
     * 
     * @return boolean 
     */
    public function hasParent()
    {
        return ($this->parent instanceof Context);
    }

    /**
     * Returns a new parent Context
     * 
     * @return Context 
     */
    public function newParent()
    {
        $ctx = new self($this->request, $this->response);
        $ctx->setParent($this);
        
        return $ctx;
    }
    
    /**
     * Tells if this context is ready (Action name is defined)
     * 
     * @return boolean
     */
    public function isReady()
    {
        return ($this->state === self::STATE_READY);
    }

    /**
     * Tells if this context encountered an error 
     * 
     * @return boolean
     */
    public function isError()
    {

        return ($this->state === self::STATE_ERROR);
    }

    /**
     * Tells if the context has been executed
     * 
     * @return boolean
     */
    public function isExecuted()
    {
        return ($this->state === self::STATE_EXECUTED);
    }

    /**
     * Tells if the context has ended execution and client's response 
     * has been defined
     * 
     * @return boolean 
     */
    public function isDone()
    {
        return ($this->state === self::STATE_DONE);
    }

    /**
     * Sets a description of the error and toggle error state
     * 
     * @param string $description Error description
     * 
     * @see ContextEvents::ERROR
     * @return void
     */
    public function setError($description)
    {
        $this->error    = $description;
        $this->state    = self::STATE_ERROR;
        
        $this->notify(
            new Event(
                ContextEvents::ERROR, 
                array('errorDesc' => $description)
            )
        );
    }

    /**
     * Returns the error description (if any)
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the action proxy if actionName is defined.
     * Toggle error state otherwise
     * 
     * $bundle MUST be defined on first call
     * 
     * @param Bundle $bundle The running bundle
     * 
     * @see ContextEvents::PROXY_READY
     * @return Proxy
     */
    public function getProxy(Bundle $bundle = null)
    {
        if (isset($this->proxy)) {
            
                return $this->proxy;
        }
        
        if (null === $bundle) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You cannot call getProxy without a Bundle'.
                    ' for the first time.'
                )
            );
        }
        
        if (!$this->isReady()) {
            $this->setError('Context not ready - action is not defined.');

            return null;
        }

        $actions    = $bundle->getDescriptor()->getActions();
        if (!isset($actions[$this->action])) {
            $this->setError(
                sprintf('Action "%s" does not exist.', $this->action)
            );

            return null;
        }

        $this->proxy        = new ActionProxy($actions[$this->action]);
        $this->proxy->setContext($this);
        $this->setMulti($bundle->rawGetAll());

        $this->notify(
            new Event(
                ContextEvents::PROXY_READY, 
                array('proxy' => $this->proxy)
            )
        );

        return $this->proxy;
    }

    /**
     * Sets state to executed and store result
     * 
     * @param mixed $result Action's result (recommended: string)
     * 
     * @see ContextEvents::EXECUTED
     * @return void
     */
    public function setResult($result)
    {
        $this->state    = self::STATE_EXECUTED;
        $this->result   = $result;
        
        $this->notify(
            new Event(
                ContextEvents::EXECUTED, 
                array('result' => $result)
            )
        );
    }

    /**
     * Defines response and sets state to Done.
     * 
     * @param Response $response Client response
     * 
     * @see ContextEvents::RESPONSE
     * @return void
     */
    public function setResponse(Response $response)
    {
        $this->state    = self::STATE_DONE;
        $this->response = $response;

        $this->notify(
            new Event(
                ContextEvents::RESPONSE, 
                array('response' => $this->response)
            )
        );
    }
}