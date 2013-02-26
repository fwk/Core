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

use Fwk\Core\CoreEvent;
use Fwk\Core\Events\EndEvent;

/**
 * The Context Listener
 *
 * This listener adds application behavior to the Context and is intended to
 * be used along with CoreListener
 *
 * @category Listeners
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class ContextListener
{
    /**
     *
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Triggered when action Proxy is loaded
     *
     * @return void
     */
    public function onProxyReady(ContextEvent $event)
    {
        $app            = $this->app;
        $proxy          = $event->proxy;
        $actionClass    = $proxy->getInstance();
        $app->notify(
            new CoreEvent(
                AppEvents::ACTION_LOADED,
                array(
                    'proxy'     => $proxy,
                    'action'    => $actionClass
                ),
                $app,
                $event->getContext()
            )
        );
    }

    /**
     * Triggered when action has been executed
     *
     * @param Event $event The event with context and action result
     *
     * @return void
     */
    public function onExecuted(ContextEvent $event)
    {
        $context        = $event->getContext();
        $result         = $event->result;
        $proxy          = $context->getActionProxy();

        $this->app->notify(
            new CoreEvent(
                AppEvents::ACTION_SUCCESS,
                array(
                    'action' => $proxy->getInstance(),
                    'result' => $result
                ),
                $this->app,
                $event->getContext()
            )
        );
    }
    
    public function onResponse(ContextEvent $event)
    {
        $this->app->notify(
            new EndEvent(
                $event->getContext()->getResponse(), 
                $this->app, 
                $event->getContext()
            )
        );
    }
}
