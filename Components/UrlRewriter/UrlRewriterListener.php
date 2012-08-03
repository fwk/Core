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
 * @category   Core
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @copyright  2011-2012 Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
namespace Fwk\Core\Components\UrlRewriter;

use Fwk\Core\CoreEvent, 
    Fwk\Xml\Map, 
    Fwk\Xml\Path, 
    Fwk\Core\Application, 
    Fwk\Core\Action\Proxy;

/**
 * This Listener allows URLs to be customized the mod_rewrite way
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class UrlRewriterListener
{
    protected $rewriter;

    public function onBoot(CoreEvent $event)
    {
        $app    = $event->getApplication();
        $rw     = $this->getRewriter($app);
        
        if ($this->rewriter instanceof Rewriter) {
            $this->rewriter->addRoutes($rw->getRoutes());
        } else {
            $this->rewriter = $rw;
        }
    }

    public function onDispatch(CoreEvent $event)
    {
        $context    = $event->getContext();
        $request    = $context->getRequest();

        $baseUri     = $request->getBaseUrl();
        $uri         = $request->getRequestUri();
        
        if(\strpos($uri, $baseUri) === 0) {
            $uri    = \substr($uri, strlen($baseUri));
        }

        $route      = $this->rewriter->getRoute($uri);
        if(!$route instanceof Route) {
            return;
        }
        
        $descriptor = $event->getApplication()->getDescriptor();
        $actionName = $route->getActionName();
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
        
        foreach ($route->getParameters() as $param) {
            $request->query->set($param->getName(), $param->getValue());
        }
        
        $actions = $descriptor->getActions();
        $proxy = new Proxy($actionName, $actions[$actionName]);
        $proxy->setContext($context);
        $context->setActionProxy($proxy);
    }

    /**
     *
     * @param Event $event
     */
    public function onBundleLoaded($event) {
        $bundle     = $event->bundle;
        $loaded     = $event->loadedBundle;

        $rw         = $this->getRewriter($loaded);

        if($this->rewriter instanceof Rewriter) {
            $this->rewriter->addRoutes($rw->getRoutes());
        }
        else {
            $this->rewriter = $rw;
        }
    }

    public function onViewHelperRegistered($event) {
        $vh = \Fwk\Core\ViewHelper\ViewHelper::getInstance();
        $vh->addListener(new RwViewHelperListener($this->rewriter));
    }

    protected function getRewriter(Application $app) {
        $descriptor = $app->getDescriptor();
        $rw         = new Rewriter();
        $result     = self::getRewritesXmlMap()->execute($descriptor);
        if(!is_array($result['rewrites'])) {
            return;
        }
        
        $it = 0;
        foreach ($result['rewrites'] as $url) {
            $it++;
            $route  = $url['route'];
            $action = $url['action'];
            
            if (empty($route)) {
                throw new \RuntimeException(sprintf('Url #%u [app: %s] has no route defined.', $it, $descriptor->getId()));
            }
            
            if(empty($action)) {
                throw new \RuntimeException(sprintf('Url #%u [app: %s] has no action defined.', $it, $descriptor->getId()));
            }

            $roote  = new Route($route);
            $roote->setActionName($action);

            foreach($url['params'] as $paramName => $param) {
                $required   = $param['required'];
                $regex      = $param['regex'];
                $default    = $param['value'];

                if(empty($paramName)) {
                    throw new \RuntimeException(sprintf('Url #%u [app: %s] has a nameless param.', $it, $descriptor->getId()));
                }
                
                if ($required == 'true' || $required == '1' || empty($required)) {
                    $required = true;
                } elseif ($required == 'false' || $required == '0') {
                    $required =  false;
                } else {
                    throw new \RuntimeException(sprintf('Url #%u [app: %s] has an unknown required value (%s).', $it, $descriptor->getId(), $required));
                }

                $roote->addParameter(new RouteParameter($paramName, $default, $regex, $required));
            }

            $rw->addRoute($roote);
        }

        return $rw;
    }
    
    /**
     *
     * @return Map 
     */
    private static function getRewritesXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/url-rewrite/url', 'rewrites')
            ->loop(true)
            ->attribute('route')
            ->attribute('action')
            ->addChildren(
                Path::factory('param', 'params')
                ->loop(true, '@name')
                ->attribute('required')
                ->attribute('regex')
                ->value('value')
            )
        );
        
        return $map;
    }
}
