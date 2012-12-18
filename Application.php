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
 * Application
 *
 * The main Application class
 *
 * @category Library
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class Application extends Object
{
    /**
     * App descriptor 
     * 
     * @var Descriptor
     */
    protected $descriptor;
    
    /**
     * Services Container
     * 
     * @var mixed
     */
    protected $services;
    
    /**
     * Last exception thrown
     * 
     * @var \Exception
     */
    protected $errorException;
    
    /**
     * Constructor
     * 
     * @param Descriptor Descriptor App descriptor
     * 
     * @return void
     */
    public function __construct(Descriptor $descriptor)
    {
        $this->descriptor   = $descriptor;
        $this->services     = new Object();
        
        // this can cause a problem if we try to add a listener
        // outside of any Loader registered namespace...
        foreach($descriptor->getListeners() as $listener)
        {
            $class = (isset($listener['class']) ? $listener['class'] : null);
            if(empty($class)) {
                throw new \InvalidArgumentException(
                    "Empty listener class",
                    $class
                );
            }
            
            $listener = new $class($this);
            $this->addListener(new $class($this));
        }
    }

    /**
     * Returns the descriptor
     * 
     * @return Descriptor
     */
    public function getDescriptor()
    {
        return $this->descriptor;
    }

    /**
     * Notify the "Boot" event
     *
     * @return void
     */
    public function boot()
    {
        $event = new CoreEvent(
            AppEvents::BOOT,
            array(),
            $this
        );

        $this->notify($event);
    }

    /**
     * Runs the App according the request
     * 
     * @param Request $request The request
     * 
     * @return Application
     */
    public function run(Request $request)
    {
        $this->boot();
        
        $context = new Context($request);
        
        $this->notify(
            CoreEvent::factory(
                AppEvents::REQUEST, 
                array(), 
                $this, 
                $context
            )
        );
        
        if (!$context->isReady()) {
            $this->notify(
                new CoreEvent(
                    AppEvents::DISPATCH,
                    array(),
                    $this,
                    $context
                )
            );
        }

        if (!$context->isReady()) {
            throw $this->setErrorException(
                new Exceptions\InvalidAction('No action found'), 
                $context
            );
        }
        
        $proxy  = $context->getActionProxy();
        $action = $proxy->getInstance();
        
        $method      = $proxy->getMethod();
        $callable    = array($action, $method);

        if (!\is_callable($callable)) {
            throw new Exceptions\InvalidAction(
                sprintf(
                    'Invalid action callback (%s::%s()', 
                    get_class($action), 
                    $method
                )
            );
        }
        
        $result = call_user_func(array($action, $method));
        $context->setResult($result);
        
        $this->notify(
            new CoreEvent(
                AppEvents::END, 
                array(
                    'result' => $result
                ),
                $this,
                $context
            )
        );
        
        return $this;
    }

    /**
     * Try to run an Application with defaults
     * 
     * @param Descriptor $descriptor Application Descriptor
     * @param string     $baseUrl    Base URI
     * 
     * @return Application
     */
    public static function autorun(Descriptor $descriptor, $baseUrl = null)
    {
        $app        = new self($descriptor);
        $request    = Request::createFromGlobals();
        
        return $app->run($request);
    }

    /**
     * Defines a Services Container
     *
     * @param mixed $services Services container
     * 
     * @return Application
     */
    public function setServices($services)
    {
        $this->services     = $services;

        return $this;
    }

    /**
     * Returns the Services Container
     * 
     * @return mixed
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Return last error exception
     * 
     * @return \Exception
     */
    public function getErrorException()
    {
        return $this->errorException;
    }

    /**
     * Sets an error exception, notify the event and returns it (so it can
     * be thrown)
     * 
     * @param \Exception $errorException To-be thrown exception
     * @param Context $context The running Context (if any)
     * 
     * @return \Exception ($errorException)
     */
    public function setErrorException(\Exception $errorException, 
        Context $context = null
    ) {
        $this->errorException = $errorException;
        
        $event = new CoreEvent(
            AppEvents::ERROR, 
            array(
                'exception' => $errorException,
                'continue'  => false
            ),
            $this,
            $context
        );
        
        $this->notify($event);
        
        return $errorException;
    }
}
