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
    Fwk\Core\Application,
    Fwk\Xml\Map,
    Fwk\Xml\Path,
    \Symfony\Component\Console\Application as CliApplication,
    Fwk\Core\ContextAware,
    Fwk\Core\ServicesAware;

/**
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class ConsoleListener
{
    /**
     *
     * @var CliApplication
     */
    protected static $app;

    /**
     * Function triggered when main Application boot
     *
     * @param CoreEvent $event Event object
     *
     * @see AppEvents::BOOT
     * @return void
     */
    public function onBoot(CoreEvent $event)
    {
        if (!self::isCLI()) {
            return;
        }

        $desc = $event->getApplication()->getDescriptor();
        self::consoleApp($desc->getId(), $desc->getVersion());
    }

    /**
     *
     * @param CoreEvent $event
     *
     * @see AppEvents::DISPATCH
     * @return void
     */
    public function onDispatch(CoreEvent $event)
    {
        if (!self::isCLI()) {
            return;
        }
        
        $desc       = $event->getApplication()->getDescriptor();
        $result     = self::getCommandsXmlMap()->execute($desc);
        $commands   = array_keys($result['commands']);
        $app        = self::$app;

        foreach ($commands as $command) {
            $cmd = new $command;

            if ($cmd instanceof ContextAware) {
                $cmd->setContext($event->getContext());
            }

            if ($cmd instanceof ServicesAware) {
                $cmd->setServices($event->getApplication()->getServices());
            }

            $app->add($cmd);
        }

        exit(self::consoleApp()->run());
    }

    /**
     *
     * @return boolean
     */
    public static function isCLI()
    {
        return (php_sapi_name() === "cli");
    }


    /**
     *
     * @param type $appName
     * @param type $version
     *
     * @return CliApplication
     */
    protected static function consoleApp($appName = null, $version = null)
    {
        if (!isset(self::$app)) {
            self::$app = new CliApplication($appName, $version);
        }

        return self::$app;
    }

    /**
     *
     * @return Map
     */
    private static function getCommandsXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/commands/command', 'commands')
            ->loop(true, '@class')
        );

        return $map;
    }
}
