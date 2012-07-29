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

use Fwk\Events\Dispatcher;

/**
 * Provides basic Dependency Injection capabilities and configuration, and
 * extends an events dispatcher. {@see Fwk\Events\Dispatcher}
 * 
 * @category Interfaces
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class Object extends Dispatcher
{
    /**
     * Items defined
     * 
     * @var array 
     */
    protected $di = array();
    
    public function __construct(array $options = array())
    {
        $this->di = $options;
    }
    
    /**
     *
     * @param string $key
     * @param mixed $default
     * 
     * @return mixed 
     */
    public function get($key, $default = null)
    {
        if(array_key_exists($key, $this->di)) {
            $val = $this->di[$key];
            if($val instanceof \Closure) {
                $val = call_user_func($val, $this);
                
                // prevent multiple instances (di)
                $this->set($key, $val);
            }
            
            return $val;
        }
        
        return $default;
    }
    
    /**
     *
     * @param string $key
     * @param mixed $default
     * 
     * @return mixed 
     */
    public function rawGet($key, $default = null)
    {
        if(array_key_exists($key, $this->di)) {
            return $this->di[$key];
        }
        
        return $default;
    }
    
    /**
     *
     * @return array 
     */
    public function rawGetAll()
    {
        return $this->di;
    }
    
    /**
     *
     * @param string $key
     * @param mixed $value
     * 
     * @return Object 
     */
    public function set($key, $value)
    {
        $this->di[$key] = $value;
        
        return $this;
    }
    
    /**
     * Defines multiples values
     * 
     * @param array $values Keys/Values to be set
     * 
     * @return Object 
     */
    public function setMulti(array $values)
    {
        foreach($values as $key => $value) {
            $this->set($key, $value);
        }
        
        return $this;
    }
   
    /**
     *
     * @param string $key
     * 
     * @return boolean 
     */
    public function has($key)
    {
        
        return array_key_exists($key, $this->di);
    }
    
    /**
     *
     * @param string $key
     * 
     * @return Object 
     */
    public function delete($key)
    {
        unset($this->di[$key]);
        
        return $this;
    }
}