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

class Loader
{
    /**
     * Instance
     * 
     * @var Loader
     */
    private static $instance;
    
    /**
     * Registered namespaces with paths
     *
     * @var array
     */
    protected $namespaces;
    
    /**
     *
     * @return Loader
     */
    public static function getInstance()
    {
        if(!isset(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
 
    
    /**
     *  Private constructor (singleton)
     */
    protected function __construct()
    {
        spl_autoload_register(array($this, 'load'));
    }
    
    
    /**
     * Registers a namespace and according paths to look for classes
     *
     * @param string $namespace
     * @param array $paths
     * @return void
     */
    public function registerNamespace($namespace, $paths = array())
    {
        if (is_string($paths)) {
            $paths = array($paths);
        }
        
        $this->namespaces[$namespace] = $paths;
    }
    
    
    /**
     * Loads a class
     *
     * @param string $className
     * @return boolean true if the class has been loaded successfuly
     */
    public function load($className) {
         if(\class_exists($className, false) || \interface_exists($className, false))
                return true;
        
        if(strpos($className,'\\') !== false) {
            list($namespace,) = explode('\\', $className);

            if(!empty($namespace) AND isset($this->namespaces[$namespace]))
                $paths = $this->namespaces[$namespace];

            $classParts = explode('\\', $className);
        }

        elseif(strpos($className,'_') !== false) {
            list($namespace,) = explode('_', $className);

            if(!empty($namespace) AND isset($this->namespaces[$namespace]))
                $paths = $this->namespaces[$namespace];

            $classParts = explode('_', $className);
        }


        /**
         * $className does not provide namespace
         * check include path
         */
        if(!isset($paths)) {
            $paths = explode(PATH_SEPARATOR, get_include_path());
            $fileName = $className .'.php';
            $namespace = null;
        }

        if(isset($classParts) AND !isset($fileName))
            $fileName = $classParts[count($classParts)-1] .'.php';

        if(is_array($paths)) {
            if(isset($classParts)) {
                array_shift($classParts);
                $possible = implode('/', $classParts) .'.php';
            } else
                $possible = $fileName;

            foreach($paths as $path) {
                $maybe = realpath(rtrim($path, '/') . DIRECTORY_SEPARATOR . $fileName);
                $maybe2 = realpath(rtrim($path, '/') . DIRECTORY_SEPARATOR . $possible);

                if(is_file($maybe) OR is_file($maybe2)) {
                    $file = (is_file($maybe2) ? $maybe2 : $maybe);

                    include $file;
                    if(\class_exists($className, false) || \interface_exists($className, false)) {
                        
                        return true;
                        
                    }
                }
            }
        }

        return false;
    }
}