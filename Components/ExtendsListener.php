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
    Fwk\Xml\Map, 
    Fwk\Xml\Path;

use Fwk\Core\AppEvents;

/**
 * This Listener adds the ability to "extends" functionnalities from another
 * Application
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class ExtendsListener
{
    /**
     * @var array
     */
    public function onBoot(CoreEvent $event)
    {
        $app    = $event->getApplication();
        $appdesc = $app->getDescriptor();
        $res    = self::getExtendsXmlMap()->execute($appdesc);
        $extds  = (is_array($res['extends']) ? $res['extends'] : array());
        
        foreach($extds as $infos) {
            $path = $infos['path'];
            
            if(strpos($path, './', 0) !== false) {
                $path = dirname($appdesc->getRealPath()) . 
                        DIRECTORY_SEPARATOR . 
                        substr($path, 2);
            }
                $xmlFile = rtrim($path, DIRECTORY_SEPARATOR) .
                        DIRECTORY_SEPARATOR .
                        'fwk.xml';
            
            try {
                $desc = new \Fwk\Core\Descriptor($xmlFile);
            } catch(\Fwk\Core\Exception $exp) {
                throw new \Fwk\Core\Exception(
                    sprintf("Invalid Extends path: %s (missing xml)", $path)
                );
            }
            
            $actions = $desc->getActions();
            $current = $appdesc->getActions();
            $appdesc->setActions(array_merge($current, $actions));
            
            $loaded = new \Fwk\Core\Application($desc);
            $loaded->setServices($app->getServices());
            $loaded->boot($app);
            
            $app->notify(
                new CoreEvent(
                    ComponentsEvents::APP_LOADED,
                    array(
                        'application' => $loaded
                    ),
                    $app,
                    $event->getContext()
                )
            ); 
        }
    }

    /**
     *
     * @return Map 
     */
    private static function getExtendsXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/extends', 'extends')
            ->loop(true)
            ->attribute('path')
        );
        
        return $map;
    }
}