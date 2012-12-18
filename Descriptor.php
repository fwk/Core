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
 * @category  Core
 * @package   Fwk\Core
 * @author    Julien Ballestracci <julien@nitronet.org>
 * @copyright 2011-2012 Julien Ballestracci <julien@nitronet.org>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://www.phpfwk.com
 */
namespace Fwk\Core;

use Fwk\Xml\XmlFile,
    Fwk\Xml\Map,
    Fwk\Xml\Path;

/**
 * "Describes" an Application.
 * 
 * @category Library
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class Descriptor extends XmlFile
{
    /**
     * Application ID (name)
     * 
     * @var string
     */
    protected $id;
    
    /**
     * Application version
     * 
     * @var string
     */
    protected $version;
    
    /**
     * Array of actions
     * 
     * @var array
     */
    protected $actions;
    
    /**
     * Array of listeners
     * 
     * @var array
     */
    protected $listeners;
    
    /**
     * Constructor
     * 
     * @param string $xml Path to XML file
     * 
     * @throws Exception if XML file not found/readable
     * @return void
     */
    public function __construct($xml)
    {
        parent::__construct($xml);
        if(!$this->exists() || !$this->isReadable()) {
            throw new Exception(
                sprintf(
                    "Descriptor '%s' not found/readable",
                    $xml
                )
            );
        }
        
        $details = self::getXmlAppDetailsMap()->execute($this);
        if(!isset($details['app']) || !is_array($details['app'])) {
            throw new Exception(
                'Descriptor XML is invalid (root element must be "fwk")'
            );
        }
        
        $app        = $details['app'];
        $id         = (isset($app['id']) ? $app['id'] : null);
        $version    = (isset($app['version']) ? $app['version'] : null);
        
        if(empty($id)) {
            throw new Exception(
                'Descriptor XML is invalid (missing "id" attribute)'
            );
        }
        
        if(empty($version)) {
            throw new Exception(
                'Descriptor XML is invalid (missing "version" attribute)'
            );
        }
        
        $this->id       = $id;
        $this->version  = $version;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }
    
    /**
     * Application's Namespace
     * 
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Application's version
     * 
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }
    
    /**
     * List of actions
     * 
     * @return array 
     */
    public function getActions()
    {
        if(!isset($this->actions)) {
            $result     = self::getXmlActionsMap()->execute($this);
            $this->actions = (isset($result['actions']) ?
                $result['actions'] : 
                array()
            );
        }
        
        return $this->actions;
    }
    
    /**
     * List of listeners
     * 
     * @return array 
     */
    public function getListeners()
    {
        if(!isset($this->listeners)) {
            $result     = self::getXmlListenersMap()->execute($this);
            $this->listeners = (isset($result['listeners']) ?
                $result['listeners'] : 
                array()
            );
        }
        
        return $this->listeners;
    }

    /**
     * Tells if an action exists
     * 
     * @return boolean
     */
    public function hasAction($actionName)
    {
        $actions = $this->getActions();
        
        return array_key_exists($actionName, $actions);
    }
    
    /**
     * Builds and returns an XML Map used to parse listeners in fwk.xml
     * 
     * @return Map
     */
    public static function getXmlListenersMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/listener', 'listeners')
            ->loop(true)
            ->attribute('class')
            ->addChildren(
                Path::factory('param', 'params')
                ->loop(true, '@name')
             )
        );
        
        return $map;
    }
    
    /**
     * Builds and returns an XML Map used to parse actions described in fwk.xml
     * 
     * @return Map
     */
    public static function getXmlActionsMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/actions/action', 'actions')
            ->loop(true, '@name')
            ->attribute('class')
            ->attribute('method')
        );
        
        return $map;
    }
    
    /**
     * Builds and returns an XML Map used to parse application's details 
     * 
     * @return Map
     */
    public static function getXmlAppDetailsMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk', 'app')
            ->attribute('id')
            ->attribute('version')
        );
        
        return $map;
    }
}