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
namespace Fwk\Core\Components;

use Fwk\Core\Events\BootEvent;
use Fwk\Di\Container;
use Fwk\Xml\Path, Fwk\Xml\Map;
use Fwk\Core\Components\Descriptor\DescriptorLoadedEvent;
use Fwk\Core\ContextAware;
use Fwk\Core\ServicesAware;
use Fwk\Core\Preparable;
use Fwk\Di\ClassDefinition;
use Fwk\Core\Events\DispatchEvent;

class ConsoleListener
{
    protected $serviceName;
    
    public function __construct($serviceName)
    {
        $this->serviceName = $serviceName;
    }
    
    /**
     *
     * @param CoreEvent $event
     *
     * @see AppEvents::DISPATCH
     * @return void
     */
    public function onDescriptorLoaded(DescriptorLoadedEvent $event)
    {
        if (!$this->isCli()) {
            return;
        }
        
        $commands   = array();
        $map        = $this->getCommandsXmlMap();
        foreach ($event->getDescriptor()->getSourcesXml() as $xml) {
            $parse  = $map->execute($xml);
            $res    = (isset($parse['commands']) ? $parse['commands'] : array());
            $commands  = array_merge($commands, $res);
        }

        $app = $this->getConsoleApplication($event->getApplication()->getServices());
        foreach ($commands as $name => $command) {
            $def = new ClassDefinition(
                $event->getDescriptor()->propertizeString($command['class']), 
                array($name)
            );
            
            $cmd = $def->invoke($event->getApplication()->getServices());

            if ($cmd instanceof ContextAware) {
                $cmd->setContext($event->getContext());
            }

            if ($cmd instanceof ServicesAware) {
                $cmd->setServices($event->getApplication()->getServices());
            }

            if ($cmd instanceof Preparable) {
                call_user_func_array(array($cmd, Preparable::PREPARE_METHOD));
            }
            
            $app->add($cmd);
        }
    }
    
    public function onDispatch(DispatchEvent $event)
    {
        if (!$this->isCli()) {
            return;
        }
        
        exit($this->getConsoleApplication($event->getApplication()->getServices())->run());
    }
    
    protected function isCli()
    {
        return (php_sapi_name() === "cli");
    }
    
    /**
     * 
     * @param \Fwk\Di\Container $container
     * 
     * @return 
     */
    protected function getConsoleApplication(Container $container)
    {
        return $container->get($this->serviceName);
    }
    
    /**
     *
     * @return Map
     */
    private function getCommandsXmlMap()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/commands/command', 'commands')
            ->loop(true, '@name')
            ->attribute('class')
        );

        return $map;
    }
}