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
namespace Fwk\Core\Components\ResultType\Types;

use Fwk\Core\Components\ResultType\ResultType;
use Symfony\Component\HttpFoundation\Response;
use Fwk\Core\Components\ViewHelper\ViewHelperAware;
use Fwk\Core\Components\UrlRewriter\UrlViewHelper;
use Fwk\Core\Components\ViewHelper\ViewHelper;

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
 * propertie.
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
 * @link       http://www.phpfwk.com
 */
class Redirect implements ResultType, ViewHelperAware
{
    /**
     * View Helper
     * 
     * @var ViewHelper
     */
    protected $viewHelper;
    
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
        if (!isset($params['action']) && !isset($params['uri'])) {
            throw new \RuntimeException(
                'Missing parameter "action" or "uri"'
            );
        }
        
        $response = new Response(null, 302);
        if (isset($params['uri']) && !empty($params['uri'])) {
            $final      = $params['uri'];
            unset($params['uri']);
            
            if (!count($params)) {
                $response->headers->set('Location', $final);
                
                return $response;
            }
            
            $final     .= '?';
            $params     = $this->inflectParameters($actionData, $params);
            $fparams    = array();
            foreach ($params as $id => $value) {
                $fparams[] = $id .'='. urlencode($value);
            }
            
            $final     .= implode('&', $fparams);
            $response->headers->set('Location', $final);
            
            return $response;
        }
        
        // create a UrlViewHelper instead of assuming it's already loaded
        $helper     = new UrlViewHelper();
        $helper->setViewHelper($this->viewHelper);
        $actionName = $params['action'];
        unset($params['action']);
        
        $response->headers->set('Location', $helper->execute(array(
            $actionName,
            $this->inflectParameters($actionData, $params)
        )));
        
        return $response;
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
    
    /**
     * Returns the View Helper
     * 
     * @return ViewHelper
     */
    public function getViewHelper()
    {
        return $this->viewHelper;
    }
    
    /**
     * Defines the View Helper
     * 
     * @param ViewHelper $viewHelper The View Helper
     * 
     * @return void
     */
    public function setViewHelper(ViewHelper $viewHelper)
    {
        $this->viewHelper = $viewHelper;
    }
}