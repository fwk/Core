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
namespace Fwk\Core\Components\ResultType;

use Fwk\Core\Components\ResultType\ResultType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fwk\Core\ServicesAware;
use Fwk\Di\Container;
use Fwk\Core\Components\UrlRewriter\UrlRewriterService;
use Fwk\Core\Components\RequestMatcher\RequestMatcher;
use Fwk\Core\ContextAware;
use Fwk\Core\Context;

/**
 * Redirect
 * 
 * This ResultType sends a redirection according to parameters defined
 * in the <result /> block.
 * 
 * - To do a static uri redirection, use parameter "uri".
 * - To redirect to an Action, use parameter "action".
 * 
 * Other parameters will be considered as url/action parameters.
 * It is possible to use an inflected parameter value to indicate an Action's 
 * property.
 * 
 * Example:
 * 
 * <result name="success" type="redirect">
 *   <param name="action">ViewList</param>
 *   <param name="slug">:slug</param>
 * </result>
 * 
 * This will redirect the user to ViewList.action?slug=[$action->slug] when the
 * Action's result is 'success'.
 * 
 * @category   Core
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.fwk.pw
 */
class RedirectResultType implements ResultType, ServicesAware, ContextAware
{
    protected $requestMatcher;
    protected $urlRewriter;
    
    protected $services;
    protected $context;
    
    public function __construct(array $params = array())
    {
        $this->requestMatcher = (isset($params['requestMatcher']) ? $params['requestMatcher'] : null);
        $this->urlRewriter = (isset($params['urlRewriter']) ? $params['urlRewriter'] : null);
    }
    
    /**
     * Sends an HTTP Redirection
     * 
     * @param array $actionData Data from the Action Controller
     * @param array $params     Parameters defined in the <result /> block of the 
     * action
     * 
     * @return Response 
     */
    public function getResponse(array $actionData = array(), 
        array $params = array()
    ) {
        if (!isset($params['actionName']) && !isset($params['uri'])) {
            throw new Exception('Missing parameter "actionName" or "uri"');
        }
        
        $httpStatus = (isset($params['http.status']) ?
            (int)$params['http.status'] :
            302
        );
        
        unset($params['http.status']);
        
        $params     = $this->inflectParameters($actionData, $params);
        
        if (isset($params['uri']) && !empty($params['uri'])) {
            $final      = $params['uri'];
            unset($params['uri']);
            
            if (!count($params)) {
                return new RedirectResponse($final, $httpStatus);
            }
            
            $final     .= '?';
            $fparams    = array();
            foreach ($params as $id => $value) {
                $fparams[] = $id .'='. urlencode($value);
            }
            
            $final     .= implode('&', $fparams);
            return new RedirectResponse($final, $httpStatus);
        }
        
        $action = $params['actionName'];
        unset($params['actionName']);
        
        return new RedirectResponse($this->calculateRedirectUri($action, $params), $httpStatus);
    }
    
    /**
     * Search for parameters ($params) where the value starts with ':' into
     * $actionData. 
     * 
     * This is useful to define parameters dynamically.
     * 
     * @param array $actionData Action's attributes
     * @param array $params     Result parameters
     * 
     * @return array result parameters with inflection magic
     */
    protected function inflectParameters(array $actionData, array $params)
    {
        $return = array();
        foreach ($params as $id => $value) {
            if (strpos($value, ':', 0) === false) {
                $return[$id] = $value;
                continue;
            }
            
            $keyName = substr($value, 1);
            if (empty($keyName) || !array_key_exists($keyName, $actionData)) {
                $return[$id] = false;
                continue;
            }
            
            $value = $actionData[$keyName];
            $return[$id] = (string)$value;
        }
        
        return $return;
    }
    
    protected function calculateRedirectUri($actionName, array $params = array())
    {
        $uri = false;
        if (null !== $this->urlRewriter) {
            $service = $this->getServices()->get($this->urlRewriter);
            if (!$service instanceof UrlRewriterService) {
                throw new Exception(
                    sprintf(
                        '"%s" is not an UrlRewriterService instance', 
                        $this->urlRewriter
                    )
                );
            }
            
            $uri = $service->reverse($actionName, $params, false);
        } 
        
        if ($uri === false && null !== $this->requestMatcher) {
            $service = $this->getServices()->get($this->requestMatcher);
            if (!$service instanceof RequestMatcher) {
                throw new Exception(
                    sprintf(
                        '"%s" is not an RequestMatcher instance', 
                        $this->requestMatcher
                    )
                );
            }
            
            $uri = $service->reverse($actionName, $params, false);
        } else {
            throw new Exception('You must specify at least a RequestMatcher Service');
        }
        
        return rtrim($this->getContext()->getRequest()->getBaseUrl(), '/'). $uri;
    }
    
    public function getServices()
    {
        return $this->services;
    }
    
    public function setServices(Container $container) 
    {
        $this->services = $container;
    }
    
    public function getContext()
    {
        return $this->context;
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
    }
}