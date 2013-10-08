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

class Descriptor
{
    protected $sources      = array();
    protected $properties   = array();
    protected $xmlFile;
    
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
        if (!is_file($iniFile)) {
            throw new Exception('INI file not found: '. $iniFile);
        } elseif (!is_readable($iniFile)) {
            throw new Exception('INI file not readable: '. $iniFile);
        }
        
        $props = parse_ini_file($iniFile, true);
        if (!is_array($props) 
            || (!isset($props[$category]) || !is_array($props[$category]))
        ) {
            throw new Exception("No properties found in: $iniFile [$category]");
        }
        
        foreach ($props as $key => $value) {
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
    
    /**
     * @return Application
     */
    public function execute(Container $services)
    {
        $app = Application::factory('', $services);
        $app->addListener(new DescriptorListener($this));
        
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
        
        return str_replace(array_keys($replaces), array_values($replaces), $str);
    }
    
    /**
     *
     * @return Descriptor 
     */
    public function reset()
    {
        $this->properties = array();
        
        return $this;
    }
    
    public function loadSourcesXml()
    {
        
    }
}