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

use Symfony\Component\HttpFoundation\Request;

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
     * Builds an app according to its descriptor and attaches
     * CoreListener to it.
     * 
     * @param Descriptor Descriptor App descriptor
     * 
     * @return void
     */
    public function __construct(Descriptor $descriptor)
    {
        $this->descriptor   = $descriptor;
        $this->addListener(new CoreListener());
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
        $event = new Event(
            AppEvents::BOOT,
            array('app' => $this)
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
            new Event(
                AppEvents::REQUEST,
                array(
                  'request' => $request, 
                  'context' => $context,
                  'app'     => $this
                )
            )
        );
        
        $context->addListener(new ContextListener($this, $context));
        $request->match($context);
        
        if (!$context->isReady() && !$context->isExecuted() && !$context->isDone()) {
            $this->notify(
                new Event(
                    AppEvents::DISPATCH,
                    array(
                        'app'      => $this,
                        'context'  => $context
                    )
                )
            );
        }

        if ($context->getAction() == null && !$context->isDone()) {
            $context->setError(
                'Invalid request - Action is not defined (404 ?).'
            );
        }
        
        $proxy = $context->getProxy($this);
        $action = $proxy->getClass();
        if ($action instanceof Preparable) {
            call_user_func(array($action, 'prepare'));
        }
        
        $context->setResult($proxy->execute());
        
        $this->notify(
            new Event(
                AppEvents::END, 
                array(
                    'app'       => $this, 
                    'response'  => $context->getResponse()
                )
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
     * @param $services Services container
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
     * 
     * @return \Exception ($errorException)
     */
    public function setErrorException(\Exception $errorException)
    {
        $this->errorException = $errorException;
        
        $event = new Event(AppEvents::ERROR, array(
            'app'       => $this,
            'exception' => $errorException,
            'continue'  => false
        ));
        
        $this->notify($event);
        
        return $errorException;
    }
}