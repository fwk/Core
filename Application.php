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
use Fwk\Core\Exceptions\Runtime as RuntimeException;
use Fwk\Core\Events\ErrorEvent, 
    Fwk\Core\Events\RequestEvent, 
    Fwk\Core\Events\DispatchEvent,
    Fwk\Core\Events\BootEvent,
    Fwk\Core\Events\EndEvent;

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
            $class  = (isset($listener['class']) ? $listener['class'] : null);
            $params = (isset($listener['params']) ? $listener['params'] : array());
            
            if(empty($class)) {
                throw new \InvalidArgumentException(
                    "Empty listener class",
                    $class
                );
            }
            
            $this->addListener(new $class($params));
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
    public function boot(Application $app = null)
    {
        $event = new BootEvent($this, $app);

        $this->notify($event);
    }

    /**
     * Runs the App according the request
     * 
     * @param Request $request The request
     * 
     * @return Application
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }
        
        $context = new Context($request);
        
        try {
            $this->boot();
            $this->notify(new RequestEvent($request, $this, $context));
            
            if (!$context->isReady()) {
                $this->notify(new DispatchEvent($this, $context));
            }

            if (!$context->isReady()) {
                throw new Exceptions\InvalidAction('No action found');
            }

            $proxy  = $context->getActionProxy();
            
            $this->notify(
                 new CoreEvent(
                     AppEvents::INIT,
                     array(
                         'request'  => $request,
                         'proxy'    => $proxy
                     ),
                     $this, 
                     $context
                 )
             );
            
            if ($context->isDone()) {
                return $this;
            }
            
            $context->setResult($result = $proxy->execute());

            if (!$context->isDone()) {
                $this->notify(new EndEvent($result, $this, $context));
            }
            
        } catch(\Exception $exp) {
            $this->notify($event = new ErrorEvent($exp, $this, $context));
            if (!$event->isStopped()) {
                throw new RuntimeException(null, null, $exp);
            }
        }
        
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
}