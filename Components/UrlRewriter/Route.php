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
 * Route
 * 
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.fwk.pw
 */
class Route
{
    /**
     *
     * @var string 
     */
    protected $actionName;

    /**
     * 
     * @var array
     */
    protected $parameters;

    /**
     *
     * @var string 
     */
    protected $uri;

    /**
     *
     * @var string 
     */
    protected $regex;

    /**
     * Constructor
     * 
     * @param string $uri 
     * 
     * @return void
     */
    public function __construct($actionName, $uri, array $parameters = array())
    {
        $this->uri          = $uri;
        $this->actionName   = $actionName;
        $this->parameters   = $parameters;
    }

    /**
     *
     * @param RouteParameter $param 
     * 
     * @return Route
     */
    public function addParameter(RouteParameter $param)
    {
        $this->params[$param->getName()] = $param;
        
        return $this;
    }

    /**
     *
     * @return string 
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     *
     * @return string 
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     *
     * @param string $uri
     * 
     * @return boolean 
     */
    public function match($url) {
        $regex      = $this->toRegularExpr();
        if (!\preg_match_all($regex, $url, $matches)) {
            return false;
        }

        $i = 0;
        foreach ($this->parameters as $param) {
            $i++;
            $result = (isset($matches[$i][0]) ? $matches[$i][0] : null);
            $param->setValue($result);
        }

        return true;
    }

    /**
     *
     * @return string 
     */
    public function toRegularExpr()
    {
        if (!isset($this->regex)) {
            $regex  = sprintf("#^%s/?$#", rtrim($this->uri,'$'));

            if(preg_match_all('#:([a-zA-Z0-9_]+)#', $this->uri, $matches)) {
                foreach($matches[1] as $paramName) {
                    try {
                        $param = $this->getParameter($paramName);

                        $required   = $param->isRequired();
                        $reg        = '('. $param->getRegex() .')';

                        if(!$required)
                            $reg    .= '?';

                        $regex  = str_replace(':'. $paramName, $reg, $regex);
                    } catch(\RuntimeException $e) {
                    }
                }
            }

            $this->regex = $regex;
        }

        return $this->regex;
    }

    /**
     *
     * @param string $name
     * 
     * @return RouteParameter
     */
    public function getParameter($name)
    {
        if(!isset($this->parameters[$name])) {
            throw new \RuntimeException(
                sprintf('Undefined route parameter "%s"', $name)
            );
        }
        
        return $this->parameters[$name];
    }

    /**
     *
     * @param string         $name
     * @param RouteParameter $paramValue 
     * 
     * @return Route
     */
    public function setParameter($name, RouteParameter $paramValue)
    {
        $this->parameters[$name] = $paramValue;
        
        return $this;
    }
    /**
     *
     * @return array<RouteParameter>
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     *
     * @param string $name
     * 
     * @return Route
     */
    public function removeParameter($name) {
        if (!isset($this->parameters[$name])) {
            throw new \RuntimeException(
                sprintf('Undefined route parameter "%s"', $name)
            );
        }
        
        unset($this->parameters[$name]);
        
        return $this;
    }

    /**
     *
     * @param array $params 
     * 
     * @return string
     */
    public function getReverse(array $params = array())
    {
        $finalParams    = array();
        $regs           = array();

        foreach($this->getParameters() as $param) {
            $paramName  = $param->getName();
            $required   = $param->isRequired();
            $regex      = $param->getRegex();
            $default    = $param->getDefault();

            if (isset($params[$paramName])) {
                $fValue = $params[$paramName];
                unset($params[$paramName]);
            } else {
                $fValue = $default;
            }
            
            if(empty($fValue) && $required) {
                return false;
            }
            
            if(!\preg_match(sprintf('#(%s)#', $regex), (string)$fValue)) {
                if($required)
                    return false;
                else
                    $fValue = null;
            }

            $finalParams[]  = $fValue;
            $finds[]        = ':'. $paramName;
            $regs[]         = sprintf('#(:%s\??)#', $paramName);
        }

        $cleanUpUri = \ltrim(\rtrim($this->uri,'$'), '^');
        $final      = preg_replace($regs, $finalParams, $cleanUpUri);
        if (count($params)) {
            $paramsStr = "?";
            foreach ($params as $key => $value) {
                $paramsStr .= urlencode($key) ."=". urlencode($value) ."&";
            }
            
            $final .= rtrim($paramsStr, '&');
        }
        
        return $final;
    }
}