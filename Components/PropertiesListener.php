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
namespace Fwk\Core\Components;

use Fwk\Core\CoreEvent, 
    Fwk\Core\Events\BootEvent, 
    Fwk\Core\Context,
    Fwk\Xml\Map, 
    Fwk\Xml\Path;

/**
 * This Listener is in charge of handling properties
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class PropertiesListener
{
    /**
     * @var array
     */
    public function onBoot(BootEvent $event)
    {
        $app = $event->getApplication();
        $desc = $app->getDescriptor();
        $res  = self::getPropertiesXmlMap()->execute($desc);
        $props = (is_array($res['properties']) ? $res['properties'] : array());
        
        foreach($props as $key => $value) {
            $value =  $this->inflectorParams($value, $app->rawGetAll());
            $app->set($key, $value);
        }
    }

    /**
     * @var array
     */
    public function onAppLoaded(CoreEvent $event)
    {
        $app = $event->loaded;
        $desc = $app->getDescriptor();
        $res  = self::getPropertiesXmlMap()->execute($desc);
        $props = (is_array($res['properties']) ? $res['properties'] : array());
        
        $app = $event->getApplication();
        foreach($props as $key => $value) {
            $currents = array_merge($app->rawGetAll(), array('packageDir' => dirname($event->getApplication()->getDescriptor()->getRealPath())));
            $value =  $this->inflectorParams($app->get($key, $value), $currents);
            $app->set($key, $value);
        }
    }
    
    /**
     *
     * @return Map 
     */
    private static function getPropertiesXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/properties/property', 'properties')
            ->loop(true, '@name')
        );
        
        return $map;
    }
    
    private function inflectorParams($value, array $params = array()) {
        $find   = array();
        $found  = array();
        
        foreach($params as $key => $param) {
            $find[]     = ':'. $key;
            $found[]    = $param;
        }

        return str_replace($find, $found, $value);
    }
}