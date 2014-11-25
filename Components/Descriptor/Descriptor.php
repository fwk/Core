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
namespace Fwk\Core\Components\Descriptor;

use Fwk\Core\Application;
use Fwk\Di\Container;
use Fwk\Xml\Map;
use Fwk\Xml\XmlFile;
use Fwk\Xml\Path;
use Fwk\Di\ClassDefinition;
use Fwk\Core\Action\ProxyFactory;
use Fwk\Di\Xml\ContainerBuilder;

class Descriptor
{
    const DEFAULT_CATEGORY  = "fwk";
    
    protected $sources      = array();
    protected $properties   = array();
    protected $propertiesMap = array();
    protected $sourcesXml   = array();
    
    /**
     * Constructor
     * 
     * @param array $sources
     * @param array $properties 
     * 
     * @return void
     */
    public function __construct($sources, array $properties = array())
    {
        if (!is_array($sources)) {
            $sources = array($sources);
        }
        
        $this->sources      = $sources;
        $this->setAll($properties);
    }
    
    public function import($sourceFile)
    {
        $this->sources[] = $sourceFile;
        
        return $this;
    }
    
    public function iniProperties($iniFile, $category = null)
    {
        if (!is_file($iniFile) || !is_readable($iniFile)) {
            throw new Exception('INI file not found/readable: '. $iniFile);
        }
        
        if (null === $category) {
            $category = self::DEFAULT_CATEGORY;
        }
        
        $props = parse_ini_file($iniFile, true);
        if (!is_array($props) 
            || (!isset($props[$category]) || !is_array($props[$category]))
        ) {
            throw new Exception("No properties found in: $iniFile [$category]");
        }
        
        foreach ($props[$category] as $key => $value) {
            $this->properties[$key] = $this->propertizeString($value);
            $this->propertiesMap[$key] = ":". $key;
        }
        
        return $this;
    }
    
    /**
     *
     * @param string $propName
     * @param string $value
     * 
     * @return Descriptor 
     */
    public function set($propName, $value)
    {
        if (is_null($value)) {
            unset($this->properties[$propName]);
            unset($this->propertiesMap[$propName]);
        } else {
            $this->properties[$propName] = $value;
            $this->propertiesMap[$propName] = ":". $propName;
        }
        
        return $this;
    }
    
    /**
     *
     * @param type $propName
     * @param type $default
     * @return type 
     */
    public function get($propName, $default = null)
    {
        return (array_key_exists($propName, $this->properties) ? 
            $this->properties[$propName] :
            $default
        );
    }
    
    /**
     *
     * @param string $propName
     * 
     * @return boolean
     */
    public function has($propName)
    {
        return array_key_exists($propName, $this->properties);
    }
    
    public function setAll(array $properties)
    {
        foreach ($this->properties as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }
    
    /**
     * @return Application
     */
    public function execute($appName, Container $services = null)
    {
        $this->sources = array_reverse($this->sources);
        
        $app = Application::factory($appName, $services);
        if (null === $services) {
            $services = $app->getServices();
        }
        
        $app->addListener(new DescriptorListener($this));
        
        $this->loadIniFiles();
        $this->loadServices($services);


        foreach ($this->loadListeners($services) as $listener) {
            $app->addListener($listener);
        }

        foreach ($this->loadPlugins($services) as $plugin) {
            $app->plugin($plugin);
        }

        foreach ($this->loadActions() as $actionName => $str) {
            $app->register($actionName, ProxyFactory::factory($str));
        }
        
        return $app;
    }
    
    /**
     *
     * @param string $str
     * 
     * @return string
     */
    public function propertizeString($str)
    {
        return str_replace(
            array_values($this->propertiesMap),
            array_values($this->properties),
            $str
        );
    }
    
    public function loadServices(Container $container)
    {
        $xml        = array();
        $map        = $this->xmlServicesMapFactory();
        foreach ($this->sources as $source) {
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = (isset($parse['services']) ? $parse['services'] : array());
            $xml[dirname($this->getSourceXml($source)->getRealPath())] = $res;
        }
        
        foreach ($xml as $baseDir => $data) {
            $this->set('baseDir', $baseDir);
            foreach ($data as $xmlFile => $infos) {
                $xmlMapClass = (!empty($infos['xmlMap']) ? 
                    $this->propertizeString($infos['xmlMap']) : 
                    null
                );
                
                if (null !== $xmlMapClass) {
                    $def = new ClassDefinition($xmlMapClass);
                    $mapObj = $def->invoke($container);
                } else {
                    $mapObj = null;
                }
                
                $builder = new ContainerBuilder($mapObj);
                $builder->execute(
                    $this->propertizeString($xmlFile), 
                    $container
                );
            }
            $this->set('baseDir', null);
        }
    }
    
    protected function loadIniFiles()
    {
        $xml        = array();
        $map        = $this->xmlIniMapFactory();
        foreach ($this->sources as $source) {
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = (isset($parse['ini']) ? $parse['ini'] : array());
            $xml[dirname($this->getSourceXml($source)->getRealPath())] = $res;
        }
        
        foreach ($xml as $baseDir => $data) {
            $this->set('baseDir', $baseDir);
            foreach ($data as $infos) {
                $cat = $this->propertizeString($infos['category']);
                $this->iniProperties(
                    $this->propertizeString($infos['value']), 
                    (empty($cat) ? null : $cat)
                );
            }
            $this->set('baseDir', null);
        }
    }
    
    public function loadListeners(Container $container)
    {
        $listeners  = array();
        $xml        = array();
        $map        = $this->xmlListenersMapFactory();
        foreach ($this->sources as $source) {
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = (isset($parse['listeners']) ? $parse['listeners'] : array());
            $xml    = array_merge($xml, $res);
        }
        
        foreach ($xml as $data) {
            $finalParams = array();
            foreach ($data['params'] as $paramData) {
                $finalParams[$paramData['name']] = $paramData['value'];
            }
            
            if (isset($data['class']) && !empty($data['class'])) {
                $def = new ClassDefinition(
                    $this->propertizeString($data['class']), 
                    $finalParams
                );
                $listeners[] = $def->invoke($container);
            } elseif (isset($data['service']) && !empty($data['service'])) {
                $listeners[] = $container->get($data['service']);
            } else {
                throw new Exception('You must specify attribute "class" or "service" for listener');
            }
        }
        
        return $listeners;
    }

    public function loadPlugins(Container $container)
    {
        $plugins  = array();
        $xml        = array();
        $map        = $this->xmlPluginsMapFactory();
        foreach ($this->sources as $source) {
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = (isset($parse['plugins']) ? $parse['plugins'] : array());
            $xml    = array_merge($xml, $res);
        }

        foreach ($xml as $data) {
            $finalParams = array();
            foreach ($data['params'] as $paramData) {
                $finalParams[$paramData['name']] = $paramData['value'];
            }

            if (isset($data['class']) && !empty($data['class'])) {
                $def = new ClassDefinition(
                    $this->propertizeString($data['class']),
                    $finalParams
                );
                $plugins[] = $def->invoke($container);
            } elseif (isset($data['service']) && !empty($data['service'])) {
                $plugins[] = $container->get($data['service']);
            } else {
                throw new Exception('You must specify attribute "class" or "service" for plugin');
            }
        }

        return $plugins;
    }

    public function loadActions()
    {
        $actions    = array();
        $xml        = array();
        $map        = $this->xmlActionMapFactory();
        foreach ($this->sources as $source) {
            $this->set('packageDir', dirname($this->getSourceXml($source)->getRealPath()));
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = (isset($parse['actions']) ? $parse['actions'] : array());
            foreach ($res as $actionName => $data) {
                $actionName = $this->propertizeString($actionName);
                if (isset($data['class']) && isset($data['method'])) {
                    $actionStr = implode(':', array(
                        $this->propertizeString($data['class']), 
                        $this->propertizeString($data['method'])
                    ));
                } elseif (isset($data['shortcut'])) {
                    $str        = $data['shortcut'];
                    $actionStr  = $this->propertizeString($str);
                }

                $actions[$actionName] = $actionStr;
            }
        }
        
        $this->set('packageDir', null);
        
        return $actions;
    }
    
    /**
     *
     * 
     * @return array
     */
    public function getSourcesXml()
    {
        return $this->sourcesXml;
    }
    
    /**
     *
     * @param string $source
     * 
     * @return XmlFile
     */
    public function getSourceXml($source)
    {
        if (!isset($this->sourcesXml[$source])) {
            $this->sourcesXml[$source] = new XmlFile($source);
        }
        
        return $this->sourcesXml[$source];
    }
    
    /**
     * Builds an XML Map used to parse listeners from an XML source
     * 
     * @return Map
     */
    protected function xmlListenersMapFactory()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/listener', 'listeners')
            ->loop(true)
            ->attribute('class')
            ->attribute('service')
            ->addChildren(
                Path::factory('param', 'params')
                ->attribute('name')
                ->filter(array($this, 'propertizeString'))
                ->value('value')
                ->loop(true)
             )
        );
        
        return $map;
    }

    /**
     * Builds an XML Map used to parse plugins from an XML source
     *
     * @return Map
     */
    protected function xmlPluginsMapFactory()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/plugin', 'plugins')
                ->loop(true)
                ->attribute('class')
                ->attribute('service')
                ->addChildren(
                    Path::factory('param', 'params')
                        ->attribute('name')
                        ->filter(array($this, 'propertizeString'))
                        ->value('value')
                        ->loop(true)
                )
        );

        return $map;
    }
    
    /**
     * Builds an XML Map used to parse Actions from an XML source
     * 
     * @return Map
     */
    protected function xmlActionMapFactory()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/actions/action', 'actions')
            ->loop(true, '@name')
            ->attribute('class')
            ->attribute('method')
            ->attribute('shortcut')
        );
        
        return $map;
    }
    
    /**
     * Builds an XML Map used to parse .ini includes
     * 
     * @return Map
     */
    protected function xmlIniMapFactory()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/ini', 'ini', array())
            ->loop(true)
            ->attribute('category')
            ->value('value')
        );
        
        return $map;
    }
    
    /**
     * Builds an XML Map used to parse services includes (xml)
     * 
     * @return Map
     */
    protected function xmlServicesMapFactory()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/services', 'services', array())
            ->loop(true, '@xml')
            ->attribute('xmlMap')
        );
        
        return $map;
    }
}