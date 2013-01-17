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
use Fwk\Core\Components\ViewHelper\ViewHelper;
use Fwk\Core\Accessor;
use Fwk\Core\Context;

/**
 * Json Result Type
 * 
 * Returns action's properties (selected in <param name="properties" />) 
 * encoded in a JSON object. ViewHelper and Context will never be sent.
 * 
 * If no parameter "properties" is set, all action's properties will be sent.
 * 
 * It is possible to change the HTTP status code to anything you want by using
 * the parameter "http.status"
 * 
 * @category   Core
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class Json implements ResultType
{
    /**
     * Sends JSON response
     * 
     * @param array $actionData Data from the Action Controller
     * @param array $sendParams Parameters defined in the <result /> block of the 
     * action
     * 
     * @return Response 
     */
    public function getResponse(array $actionData = array(), 
        array $sendParams = array()
    ) {
        $httpStatus = (isset($sendParams['http.status']) ?
            (int)$sendParams['http.status'] :
            200
        );
        
        $params = (isset($sendParams['properties']) ? 
            array_map(
                function($n) { return trim($n); }, 
                explode(',', $sendParams['properties'])
            ) :
            array()
        );
        
        if (!count($params)) {
            $params = array_keys($actionData);
        }
        
        foreach ($actionData as $key => $value) {
            if (!in_array($key, $params, true)) {
                unset($actionData[$key]);
            }
        }
        
        $response = new Response(null, $httpStatus);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(static::prepareForJson($actionData)));
        
        return $response;
    }
    
    public static function prepareForJson($data)
    {
        $final = array();
        foreach ($data as $key => $value) {
            if ($value instanceof ViewHelper || $value instanceof Context) {
                continue;
            }
            
            $final[$key] = static::singleValueModifier($value);
        }
        
        return $final;
    }
    
    public static function singleValueModifier($value)
    {
        if (is_numeric($value) || is_int($value) || is_float($value)) {
            return $value;
        } elseif (is_string($value)) {
            return $value;
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        } elseif (is_bool($value)) {
            return (string)$value;
        } elseif ($value instanceof \Fwk\Db\Relation) {
            return static::prepareForJson($value->getRegistry()->toArray());
        } elseif (is_array($value) || $value instanceof \ArrayAccess) {
            return static::prepareForJson($value);
        } elseif (is_object($value)) {
            $accessor = new Accessor($value);
            return static::prepareForJson($accessor->toArray('singleValueModifier'));
        } 
        
        return null;
    }
}