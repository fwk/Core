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

use Fwk\Core\Events\DispatchEvent;
use Fwk\Core\Components\Descriptor\DescriptorLoadedEvent;
use Fwk\Core\Components\Descriptor\Descriptor;
use Fwk\Xml\Map, Fwk\Xml\Path;

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
    protected $serviceName;
    
    public function __construct($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    public function onDispatch(DispatchEvent $event)
    {
        $request = $event->getContext()->getRequest();
        $baseUri = $request->getBaseUrl();
        $uri     = $request->getRequestUri();

        if(!empty($baseUri) && \strpos($uri, $baseUri) === 0) {
            $uri = \substr($uri, strlen($baseUri));
        }
        
        if (strpos($uri, '?') !== false) {
            list($uri,) = explode('?', $uri);
        } elseif (empty($uri)) {
            $uri = '/';
        }
        
        $route = $event->getApplication()
                ->getServices()
                ->get($this->serviceName)
                ->getRoute($uri);
        
        if (!$route instanceof Route) {
            return;
        }

        $actionName = $route->getActionName();
        if (!$event->getApplication()->exists($actionName)) {
            throw new Exception(sprintf("Unknown action '%s'", $actionName));
        }

        foreach ($route->getParameters() as $param) {
            $request->query->set($param->getName(), $param->getValueOrDefault());
        }

        $event->getContext()->setActionName($actionName);
    }
    
    public function onDescriptorLoaded(DescriptorLoadedEvent $event)
    {
        $results    = array();
        $map        = $this->xmlRewritesMapFactory($event->getDescriptor());
        foreach ($event->getDescriptor()->getSourcesXml() as $xml) {
            $parse      = $map->execute($xml);
            $res        = (isset($parse['rewrites']) ? $parse['rewrites'] : array());
            $results    = array_merge($results, $res);
        }
        
        $rewriter   = $event->getApplication()
                ->getServices()
                ->get($this->serviceName);
        
        $it = 0;
        foreach ($results as $url) {
            $it++;
            $roote  = new Route(
                $event->getDescriptor()->propertizeString($url['action']), 
                $event->getDescriptor()->propertizeString($url['route'])
            );

            foreach($url['params'] as $paramName => $param) {
                $required   = $param['required'];
                $regex      = $param['regex'];
                $default    = $param['value'];

                if ($required == 'true' || $required == '1' || empty($required)) {
                    $required = true;
                } elseif ($required == 'false' || $required == '0') {
                    $required =  false;
                } 

                $roote->addParameter(new RouteParameter($paramName, $default, $regex, $required));
            }

            $rewriter->addRoute($roote);
        }
    }
    
    /**
     *
     * @return Map
     */
    protected function xmlRewritesMapFactory(Descriptor $desc)
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
                ->filter(array($desc, 'propertizeString'))
                ->value('value')
            )
        );

        return $map;
    }
}
