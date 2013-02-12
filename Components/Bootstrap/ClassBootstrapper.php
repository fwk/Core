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
/**
 * This is a bootstrapper class that runs all public methods starting by
 * 'register' like registerServices, registerDatabase ... from a class given in
 * constructor
 * 
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class ClassBootstrapper implements Bootstrapper
{

    const PREFIX = 'register';

    /**
     * Bootstrap'd ?
     * 
     * @var boolean
     */
    protected $booted = false;

    /**
     * The bootstrap class
     *
     * @var mixed
     */
    protected $object;

    /**
     * Constructor
     *
     * @param mixed $class The bootstrap class
     * 
     * @return void
     */
    public function __construct($class)
    {
        if (!\is_object($class)) {
            throw new \RuntimeException(
                sprintf('Specified parameter is not an object.')
            );
        }

        $this->object = $class;
    }

    /**
     * Bootstraps the bundle
     *
     * @return void
     */

    public function boot(\Fwk\Core\Application $app)
    {
        if ($this->booted) {
            return;
        }

        $reflect = new \ReflectionClass($this->object);
        foreach ((array)$reflect->getMethods() as $method) {
            if ($method instanceof \ReflectionMethod) {
                if (!$method->isPublic()) {
                        continue;
                }

                if (strpos($method->getName(), self::PREFIX) !== 0) {
                        continue;
                }

                $params = (count($method->getParameters()) ? 
                            array($app) : 
                            array()
                          );

                \call_user_func_array(
                    array($this->object, $method->getName()), 
                    $params
                );
            }
        }

        $this->booted = true;
    }

    /**
     * Tells if this bootstrap has already booted
     *
     * @return boolean
     */
    public function isBootstrapped()
    {
        return $this->booted;
    }
}