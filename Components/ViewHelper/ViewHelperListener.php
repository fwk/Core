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
namespace Fwk\Core\Components\ViewHelper;

use Fwk\Core\CoreEvent,
    Fwk\Xml\Map,
    Fwk\Xml\Path,
    Fwk\Core\Application,
    Fwk\Core\Components\ComponentsEvents;

/**
 * This Listener adds a ViewHelper available in templates when rendering
 * an action.
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class ViewHelperListener
{
    /**
     * @var ViewHelper
     */
    protected static $helper;

    /**
     *
     * @param CoreEvent $event
     *
     * @return void
     */
    public function onBoot(CoreEvent $event)
    {
        if (!isset(self::$helper)) {
            self::$helper   = new ViewHelper($event->getContext());
        }

        $app            = $event->getApplication();
        $desc           = $app->getDescriptor();
        $results        = self::getViewHelpersXmlMap()->execute($desc);

        $helpers    = (is_array($results['helpers']) ?
            $results['helpers'] :
            array()
        );

        foreach ($helpers as $name => $infos) {
            $params = (isset($infos['params']) && is_array($infos['params']) ?
                        $infos['params'] :
                        array()
                      );

            $helper = new $infos['class']($params);

            self::$helper->add($name, $helper);
        }

        $app->notify(
            new CoreEvent(
                ComponentsEvents::VIEWHELPER_REGISTERED,
                array('viewHelper' => self::$helper),
                $app,
                $event->getContext()
            )
        );
    }


    /**
     *
     * @param CoreEvent $event
     *
     * @return void
     */
    public function onActionLoaded(CoreEvent $event)
    {
        self::$helper->setContext($event->getContext());
    }

    /**
     *
     * @return Map
     */
    private static function getViewHelpersXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/view-helper/helper', 'helpers')
            ->loop(true, '@name')
            ->attribute('name')
            ->attribute('class')
            ->addChildren(
                Path::factory('param', 'params')
                ->loop(true, '@name')
                ->value('value')
            )
        );

        return $map;
    }
}