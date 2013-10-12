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

class Descriptor
{
    protected $sources      = array();
    protected $properties   = array();
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
        $this->properties   = $properties;
    }
    
    public function import($sourceFile)
    {
        $this->sources[] = $sourceFile;
        
        return $this;
    }
    
    public function iniProperties($iniFile, $category = 'fwk')
    {
        if (!is_file($iniFile) || !is_readable($iniFile)) {
            throw new Exception('INI file not found/readable: '. $iniFile);
        }
        
        $props = parse_ini_file($iniFile, true);
        if (!is_array($props) 
            || (!isset($props[$category]) || !is_array($props[$category]))
        ) {
            throw new Exception("No properties found in: $iniFile [$category]");
        }
        
        foreach ($props[$category] as $key => $value) {
            $props[$key] = $this->propertizeString($value);
        }
        
        $this->properties = array_merge($this->properties, $props);
        
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
        } else {
            $this->properties[$propName] = $value;
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
        $this->properties = array_merge($this->properties, $properties);
        
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
        
        foreach ($this->loadListeners($services) as $listener) {
            $app->addListener($listener);
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
        $replaces = array();
        foreach ($this->properties as $key => $val) {
            if (is_string($val)) {
                $replaces[':'. $key] = $val;
            }
        }
        
        return str_replace(
            array_keys($replaces), 
            array_values($replaces), 
            $str
        );
    }
    
    public function loadListeners(Container $container)
    {
        $listeners  = array();
        $xml        = array();
        $map        = $this->xmlListenersMapFactory();
        foreach ($this->sources as $source) {
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = $parse['listeners'];
            $xml    = array_merge($xml, $res);
        }
        
        foreach ($xml as $className => $data) {
            $finalParams = array();
            foreach ($data['params'] as $paramData) {
                $finalParams[$paramData['name']] = $paramData['value'];
            }
            
            $def = new ClassDefinition(
                $this->propertizeString($className), 
                $finalParams
            );
            $listeners[] = $def->invoke($container);
        }
        
        return $listeners;
    }
    
    public function loadActions()
    {
        $actions    = array();
        $xml        = array();
        $map        = $this->xmlActionMapFactory();
        foreach ($this->sources as $source) {
            $parse  = $map->execute($this->getSourceXml($source));
            $res    = $parse['actions'];
            $xml    = array_merge($xml, $res);
        }
        
        foreach ($xml as $actionName => $data) {
            $actionName = $this->propertizeString($actionName);
            if (isset($data['class']) && isset($data['method'])) {
                $actionStr = implode(':', array(
                    $this->propertizeString($data['class']), 
                    $this->propertizeString($data['method'])
                ));
            } elseif (isset($data['shortcut'])) {
                $actionStr = $this->propertizeString($data['shortcut']);
            }
            
            $actions[$actionName] = $actionStr;
        }
        
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
            ->loop(true, '@class')
            ->attribute('class')
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
}