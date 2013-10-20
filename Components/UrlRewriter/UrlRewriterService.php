<?php
/**
 * Fwk
 *
 * Copyright (c) 2011-2014, Julien Ballestracci <julien@nitronet.org>.
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
 * @copyright  2011-2014 Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.fwk.pw
 */
namespace Fwk\Core\Components\UrlRewriter;

/**
 * URL Rewriter Service
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.fwk.pw
 */
class UrlRewriterService
{
    protected $routes = array();

    /**
     * Adds a route
     *
     * @param Route $route The Route
     *
     * @return UrlRewriterService
     */
    public function addRoute(Route $route)
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     *
     * @param string $url
     *
     * @return Route
     */
    public function getRoute($url)
    {
        foreach ($this->routes as $route) {
            if ($route->match($url)) {
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Returns all routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     *
     * @param array $routes
     *
     * @return UrlRewriterService
     */
    public function addRoutes(array $routes)
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    /**
     *
     * @param string $actionName
     * @param array  $params
     *
     * @return string
     */
    public function reverse($actionName, array $params = array(), 
        $escapeAmp = false
    ) {
        $possibles = array();
        foreach ($this->routes as $x => $route) {
            if ($route->getActionName() != $actionName) {
                continue;
            }

            $idx = 1 + $x;
            if (count($params) != count($route->getParameters())) {
                $idx = 99999 + $x;
            }
            
            $possibles[$idx] = $route;
        }
        
        ksort($possibles);
        
        foreach ($possibles as $route) {
            $reverse = $route->getReverse($params, $escapeAmp);
            if ($reverse !== false) {
                return $reverse;
            }
        }

        return false;
    }
}
