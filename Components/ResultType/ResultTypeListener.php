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
namespace Fwk\Core\Components\ResultType;

use Fwk\Core\Context, 
    Fwk\Core\CoreEvent, 
    Fwk\Core\Accessor,
    Fwk\Core\Application, 
    Fwk\Xml\Map, 
    Fwk\Xml\Path, 
    Fwk\Core\Loader, 
    Symfony\Component\HttpFoundation\Response;

/**
 * This Listener is in charge of handling a result (string) from an action and
 * generate an appropriate Response.
 *
 * Defined in fwk.xml as <result-type />
 * 
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class ResultTypeListener
{
    /**
     * Results types from XML
     * 
     * @var array
     */
    protected $types;

    /**
     * Action's defined results from XML
     * 
     * @var array
     */
    protected $results;

    /**
     * Default result type
     * 
     * @var string
     */
    protected $default;

    protected $viewHelperEnabled = false;
  
    public function onBoot(CoreEvent $event)
    {
        $this->types = $this->getAppResultTypes($event->getApplication());
    }
    
    public function onResult(CoreEvent $event)
    {
        $context    = $event->getContext();
        $app        = $event->getApplication();
        $result     = strtolower($event->result);
        $proxy      = $context->getActionProxy();
        
        if(!\is_string($result)) {
            return;
        }

        $actionName     = $context->getActionProxy()->getName();
        $results        = $this->getActionResults($app, $actionName);
        
        if(!isset($results[$result])) {
            return;
        }
        
        $final = $results[$result];
        if (empty($final['type'])) {
             throw new Exception(
                sprintf(
                    'Missing Result Type for Action Result "%s"', 
                    $result
                )
            );
        }
        
        $data           = $this->getActionData($proxy->getInstance());
        $params         = $final['params'];
        $typeInstance   = $this->loadType($final['type'], $app);
        
        $response       = $typeInstance->getResponse($data, $params);
        if ($response instanceof Response) {
            $context->setResponse($response);
        }
    }

    protected function loadType($typeName, Application $app)
    {
        if(!isset($this->types[$typeName])) {
             throw new Exception(
                sprintf('Unknown Result Type "%s"', $typeName)
            );
        }
        
        $type           = $this->types[$typeName];
        $appParams      = $app->rawGetAll();
        $appParams['packageDir'] = dirname($app->getDescriptor()->getRealPath());
        $params         = array();
        
        foreach ($type['params'] as $key => $value)
        {
            $params[$key] = $this->inflectorParams($value, $appParams);
        }
        
        $typeInstance   = $this->instanceOfType($type['class'], $params);
        
        return $typeInstance;
    }
    
    public function onViewHelperRegistered($event) {
        $vh = \Fwk\Core\ViewHelper\ViewHelper::getInstance();
        $vh->addListener(new ViewParamsListener());
        $this->viewHelperEnabled = true;

        $vh->set('helper', $vh);
    }
    
    /**
     * Fetches actions parameters using Reflection and returns an array of
     * key/value pairs.
     * 
     * @param object $action
     * @return array
     */
    protected function getActionData($action)
    {
        $accessor   = new Accessor($action);

        /*
        if($this->viewHelperEnabled) {
            $vh = \Fwk\Core\ViewHelper\ViewHelper::getInstance();
            $params = $vh->getParameters();

            $data   = \array_merge($data, $params);
        }
         */

        return $accessor->toArray();
    }

    /**
     * Returns an instance of the Type class
     * 
     * @return ResultType
     */
    protected function instanceOfType($className, $params = array()) {
        $instance   = new $className($params);

        if(!$instance instanceof ResultType) {
            throw new \RuntimeException(
                sprintf(
                    '"%s" is not an instance of Fwk\Core\Components\ResultType\ResultType', 
                    $className
                )
            );
        }
        
        return $instance;
    }
   
    protected function inflectorParams($value, array $params = array())
    {
        $find   = array();
        $found  = array();

        foreach ($params as $key => $param) {
            $find[]     = ':'. $key;
            $found[]    = $param;
        }

        return str_replace($find, $found, $value);
    }
    
    protected function getActionResults(Application $app, $actionName)
    {
        $results    = array();
        $desc       = $app->getDescriptor();
        $res        = self::getActionResultsXmlMap($actionName)->execute($desc);
        
        $results = (is_array($res['results']) ? 
            $res['results'] : 
            array()
        );
        
        return $results;
    }


    protected function getAppResultTypes(Application $app)
    {
        $types          = array();
        $desc           = $app->getDescriptor();
        $results        = self::getResultsTypesXmlMap()->execute($desc);
        
        $this->types    = (is_array($results['types']) ? 
            $results['types'] : 
            array()
        );
        
        return $this->types;
    }
    
    /**
     *
     * @return Map 
     */
    private static function getResultsTypesXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/result-types/result-type', 'types')
            ->loop(true, '@name')
            ->attribute('class')
            ->addChildren(
                 Path::factory('param', 'params')
                ->loop(true, '@name')
            )
        );
        
        return $map;
    }
    
    /**
     *
     * @return Map 
     */
    private static function getActionResultsXmlMap($actionName)
    {
        $map = new Map();
        $map->add(
            Path::factory(
                sprintf("/fwk/actions/action[@name='%s']/result", $actionName), 
                'results'
            )
            ->loop(true, '@name')
            ->attribute('type')
            ->addChildren(
                 Path::factory('param', 'params')
                ->loop(true, '@name')
            )
        );
        
        return $map;
    }
}