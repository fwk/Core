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
    Symfony\Component\HttpFoundation\Response,
    Fwk\Core\Components\ViewHelper\ViewHelper,
    Fwk\Core\Components\ViewHelper\ViewHelperAware,
    Fwk\Core\Exception;

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
    protected static $types = array();

    /**
     * @var \Fwk\Core\Components\ViewHelper\ViewHelper
     */
    protected $viewHelper;

    public function onBoot(CoreEvent $event)
    {
        $types = $this->getAppResultTypes($event->getApplication());

        self::$types = array_merge(
            self::$types,
            $types
        );
    }
    

    private function getAppsForAction($actionName)
    {
        $apps = array();
        
        foreach(self::$types as $typeName => $infos) {
            $desc = $infos['app']->getDescriptor();
            if($desc->hasAction($actionName)) {
                $apps[$desc->getId()] = $infos['app'];
            }
        }

        return $apps;
    }

    public function onResult(CoreEvent $event)
    {
        $context    = $event->getContext();
        $app        = $event->getApplication();
        $result     = strtolower($event->result);
        $proxy      = $context->getActionProxy();
        $ajax       = $context->getRequest()->isXmlHttpRequest();
        
        if(!\is_string($result)) {
            return;
        }

        $actionName     = $context->getActionProxy()->getName();
        $results        = $this->getActionResults($actionName);

        if(!isset($results[$result]) && ($ajax && !isset($results['ajax:'. $result]))) {
            return;
        }

        $final = (($ajax && isset($results['ajax:'. $result])) ? 
            $results['ajax:'. $result] : 
            (isset($results[$result]) ? $results[$result] : null)
        );
        
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
        $appId          = $final['appId'];
        $typeInstance   = $this->loadType($appId .'.'. $final['type'], $app->rawGetAll());

        $response       = $typeInstance->getResponse($data, $params);
        if ($response instanceof Response) {
            $context->setResponse($response);
        }
    }

    protected function loadType($typeName, array $properties = array())
    {
        if(!isset(self::$types[$typeName])) {
             throw new Exception(
                sprintf('Unknown Result Type "%s"', $typeName)
            );
        }

        $app            = self::$types[$typeName]['app'];
        $type           = self::$types[$typeName];
        $appParams      = $properties;
        $appParams['packageDir'] = dirname($app->getDescriptor()->getRealPath());
        $params         = array();

        foreach ($type['params'] as $key => $value)
        {
            $params[$key] = $this->inflectorParams($value, $appParams);
        }

        $typeInstance   = $this->instanceOfType($type['class'], $params);

        return $typeInstance;
    }

    public function onViewHelperRegistered(CoreEvent $event)
    {
        $this->viewHelper = $event->viewHelper;
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
        $data       = $accessor->toArray();

        if ($this->viewHelper instanceof ViewHelper) {
            $data[ViewHelper::PROP_NAME] = $this->viewHelper;
        }

        return $data;
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
        
        if ($instance instanceof ViewHelperAware && 
            $this->viewHelper instanceof ViewHelper) {
            $instance->setViewHelper($this->viewHelper);
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

    protected function getActionResults($actionName)
    {
        $final      = array();
        $apps       = $this->getAppsForAction($actionName);
        foreach($apps as $app) {
            $desc       = $app->getDescriptor();
            $res        = self::getActionResultsXmlMap($actionName)->execute($desc);

            $results = (is_array($res['results']) ?
                $res['results'] :
                array()
            );
            
            foreach ($results as &$result) {
                $result['appId'] = $desc->getId();
            }
            
            $final  += $results;
        }

        return $final;
    }


    protected function getAppResultTypes(Application $app)
    {
        $types          = array();
        $desc           = $app->getDescriptor();
        $results        = self::getResultsTypesXmlMap()->execute($desc);

        $appId          = $app->getDescriptor()->getId();
        $typesRes = (is_array($results['types']) ?
            $results['types'] :
            array()
        );

        $types = array();
        foreach($typesRes as $typeName => $type) {
            $types[$appId . '.' . $typeName] = $type;
            $types[$appId . '.' . $typeName]['app'] = $app;
        }
        
        return $types;
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