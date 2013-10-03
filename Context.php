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

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

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
 * @package    Fwk\Core
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class Context
{
    const STATE_ERROR       = -1;
    const STATE_INIT        = 0;
    const STATE_READY       = 1;
    const STATE_EXECUTED    = 2;
    const STATE_DONE        = 3;

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
     * The action name
     * 
     * @var string
     */
    protected $actionName;
    
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
        return ($this->state >= self::STATE_READY);
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
        return ($this->state >= self::STATE_EXECUTED);
    }

    /**
     * Tells if the context has ended execution and client's response 
     * has been defined
     * 
     * @return boolean 
     */
    public function isDone()
    {
        return ($this->state >= self::STATE_DONE);
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
    }
    
    /**
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
    
    public function getActionName()
    {
        return $this->actionName;
    }

    public function setActionName($actionName)
    {
        if ($actionName !== false) {
            $this->actionName = $actionName;
            if (!empty($actionName)) {
                $this->state = self::STATE_READY;
            }
        } else {
            $this->setError('No action found');
        }
    }
}