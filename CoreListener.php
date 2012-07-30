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

use Fwk\Events\Event, 
    Symfony\Component\HttpFoundation\Request, 
    Symfony\Component\HttpFoundation\Response;

/**
 * @category Listeners
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class CoreListener
{
    const ACTION_REGEX  =   '/^([A-Z0-9a-z_][^\.]+)\.action/';

    protected function match(Request $request, Context $context)
    {
        $baseUri     = $request->getBaseUrl();
        $uri         = $request->getRequestUri();
        
        if(\strpos($uri, $baseUri) === 0) {
            $uri    = \substr($uri, strlen($baseUri));
        }

        $uri         = trim($uri, '/');
        $actionName  = null;
        
        if (\preg_match(self::ACTION_REGEX, $uri, $matches)) {
            $actionName = $matches[1];
        }
        
        return $actionName;
    }
    
    
    public function onBoot(CoreEvent $event)
    {
        $app = $event->getApplication();
        
        $loader = Loader::getInstance();
        $loader->registerNamespace(
            $app->getDescriptor()->getId(), 
            dirname($app->getDescriptor()->getRealPath())
        );
        
    }
    
    /**
     *
     * @param CoreEvent $event
     * 
     * @throws Exceptions\InvalidAction If unknown action name submitted by 
     *                                  request
     * 
     * @return void 
     */
    public function onRequest(CoreEvent $event)
    {
        $context    = $event->getContext();
        $app        = $event->getApplication();
        $request    = $context->getRequest();
        $context->addListener(new ContextListener($app));
        
        $actionName = $this->match($request, $context);
        
        if (empty($actionName)) {
            return;
        }
        
        $descriptor = $app->getDescriptor();
        if(!$descriptor->hasAction($actionName)) {
            throw $app->setErrorException(
                new Exceptions\InvalidAction(
                    sprintf(
                        "Unknown action '%s'", 
                        $actionName
                    )
                ), 
                $context
            );
        }
        
        $actions = $descriptor->getActions();
        $proxy = new Action\Proxy($actionName, $actions[$actionName]);
        $proxy->setContext($context);
        
        $context->setActionProxy($proxy);
    }
    
    /**
     * Triggered when action class is instanciated
     * 
     * @param CoreEvent $event The event
     * 
     * @return void
     */
    public function onActionLoaded(CoreEvent $event)
    {
        $context = $event->getContext();
        $action  = $event->action;
        $app     = $event->getApplication();

        if ($action instanceof ContextAware) {
            $action->setContext($context);
        }
        
        if ($action instanceof ServicesAware) {
            $action->setServices($app->getServices());
        }
        
        if ($action instanceof Preparable) {
            call_user_func(array($action, 'prepare'));
        }
    }
    
    /**
     * Triggered when action has successfully executed and returned some result
     * 
     * @return void 
     */
    public function onActionSuccess(CoreEvent $event)
    {
        $result     = $event->result;
        $context    = $event->getContext();
        $app        = $event->getApplication();

        // action returned a response directly so let's move on to the next step
        if ($result instanceof Response) {
            $context->setResponse($result);
            return;
        }

        $app->notify(
            new CoreEvent(
                AppEvents::RESULT,
                array(
                    'result'    => $result
                ),
                $app,
                $context
            )
        );
    }
    
    
    /**
     * Triggered when Response has been defined 
     * 
     * @return void
     */
    public function onEnd(CoreEvent $event)
    {
        $response   = $event->getContext()->getResponse();
        if($response === null) {
            $response = new Response($event->result);
        }
        
        $event->getApplication()->notify(
            new CoreEvent(
                AppEvents::FINAL_RESPONSE,
                array(
                    'response' => $response
                ),
                $event->getApplication(),
                $event->getContext()
            )
        );
        
        $response->send();
    }
}