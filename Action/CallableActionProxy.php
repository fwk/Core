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
 * @category  Core
 * @package   Fwk\Core
 * @author    Julien Ballestracci <julien@nitronet.org>
 * @copyright 2011-2014 Julien Ballestracci <julien@nitronet.org>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://www.fwk.pw
 */
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;
use Fwk\Core\Application;
use Fwk\Core\Context;

/**
 * ActionProxy to a Closure or a Callable. 
 *
 * @category ActionProxy
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.fwk.pw
 */
class CallableActionProxy implements ActionProxy
{
    const PARAM_CONTEXT_NAME = 'context';
    const PARAM_SERVICES_NAME = 'services';
    
    /**
     * The Closure or Callable 
     * 
     * @var mixed
     */
    protected $callable;
    
    /**
     *
     * @var array
     */
    protected $actionData = array();
    
    /**
     * Constructor
     * 
     * @param mixed $callable Closure or Callable
     * 
     * @throws \InvalidArgumentException if invalid $callable
     * @return void
     */
    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("The closure is not callable");
        }
        
        $this->callable = $callable;
    }
    
    /**
     * Executes the callable and returns the result
     * 
     * @param Application $app     The running Application
     * @param Context     $context Actual context
     * 
     * @return mixed The controller's result
     */
    public function execute(Application $app, Context $context)
    {
        if ($this->callable instanceof \Closure) {
            $refFunc = new \ReflectionFunction($this->callable);

            $params  = array();
            $request = $context->getRequest();
            foreach ($refFunc->getParameters() as $param) {
                if ($param->getName() == self::PARAM_CONTEXT_NAME) {
                    $params[] = $context;
                } elseif ($param->getName() == self::PARAM_SERVICES_NAME) {
                    $params[] = $app->getServices();
                } else {
                    $params[] = $request->get($param->getName(), null);
                }
            }
            
            $result = call_user_func_array($this->callable, $params);
        } else {
            $result = call_user_func($this->callable);
        }
        
        if (is_array($result)) {
            $this->actionData = $result;
        }
        
        return $result;
    }
    
    public function getActionData()
    {
        return $this->actionData;
    }
    
    public function setActionData(array $data)
    {
        $this->actionData = $data;
    }
}