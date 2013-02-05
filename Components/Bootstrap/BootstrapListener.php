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
namespace Fwk\Core\Components\Bootstrap;

use Fwk\Core\Application,
    Fwk\Xml\Map,
    Fwk\Xml\Path,
    Fwk\Core\Events\BootEvent;

/**
 * This listener adds ability to define bootstrapping classes to help loading
 * services and other things at boot time.
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class BootstrapListener
{
    /**
     * Function triggered when main Application boot
     *
     * @param BootEvent $event Event object
     *
     * @return void
     */
    public function onBoot(BootEvent $event)
    {
        $this->bootstrap(
            $event->getApplication(), 
            $event->getParentApplication()
        );
    }

    /**
     * Bootstraps the specified Bundle
     *
     * @param Application $loaded Loaded bundle
     *
     * @return void
     */
    protected function bootstrap(Application $app, Application $parent = null)
    {
        $desc = $app->getDescriptor();
        $bootstraps = self::getBootstrapsXmlMap()->execute($desc);

        if(!is_array($bootstraps['bootstraps'])) {
            return;
        }

        foreach($bootstraps['bootstraps'] as $bootinfo) {
            $class = $bootinfo['class'];
            $type = $bootinfo['type'];

            $boot = new $type(new $class);
            if(!$boot instanceof Bootstrapper) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Class '%s' is not a valid Bootstrapper",
                        $type
                    )
                );
            }

            $boot->boot((null !== $parent ? $parent : $app));
        }
    }

    /**
     *
     * @return Map
     */
    private static function getBootstrapsXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/bootstrap', 'bootstraps')
            ->loop(true)
            ->attribute('type', 'type')
            ->value('class')
        );

        return $map;
    }
}