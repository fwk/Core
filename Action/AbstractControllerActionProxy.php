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
 * @link      http://www.fwk.pw
 */
namespace Fwk\Core\Action;

use Fwk\Core\ContextAware;
use Fwk\Core\ServicesAware;
use Fwk\Core\Preparable;
use Fwk\Core\Application;
use Fwk\Core\Context;
use Fwk\Core\ActionProxy;
use Symfony\Component\HttpFoundation\Request;
use Fwk\Core\Accessor;

/**
 * Abstract class grouping functions/methods to manipulate controller classes.
 *
 * @category ActionProxy
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.fwk.pw
 */
abstract class AbstractControllerActionProxy implements ActionProxy
{
    protected $actionData = array();
    
    /**
     * Instantiates the controller class (must be overriden)
     * 
     * @param Application $app The running Application
     * 
     * @abstract
     * @return mixed
     */
    abstract protected function instantiate(Application $app);
    
    /**
     * Populates action class according to request params
     *
     * @param mixed   $class   Action's class
     * @param Request $request Current request
     *
     * @return void
     */
    protected function populate($class, Request $request)
    {
        $accessor = new Accessor($class);
        $props    = $accessor->getAttributes();
        foreach ($props as $key) {
            $value = $request->get($key, false);
            if (false !== $value) {
                $accessor->set($key, $value);
            }
        }
    }
    
    /**
     * Applies rules for ServicesAware, ContextAware and Preparable interfaces.
     * 
     * @param mixed       $instance Controller instance
     * @param Application $app      The running Application
     * @param Context     $context  Actual context
     * 
     * @return void
     */
    protected function populateCoreInterfaces($instance, Application $app, 
        Context $context
    ) {
        if ($instance instanceof ContextAware) {
            $instance->setContext($context);
        }
        
        if ($instance instanceof ServicesAware) {
            $instance->setServices($app->getServices());
        }
        
        if ($instance instanceof Preparable) {
            call_user_func(array($instance, Preparable::PREPARE_METHOD));
        }
    }
    
    /**
     * Executes the controller's defined method
     * 
     * @param Application $app     The running Application
     * @param Context     $context Actual context
     * 
     * @return mixed The controller's result
     */
    public function execute(Application $app, Context $context)
    {
        $instance = $this->instantiate($app);
        
        $this->populate($instance, $context->getRequest());
        $this->populateCoreInterfaces($instance, $app, $context);
        
        $return = call_user_func(array($instance, $this->method));
        
        $accessor = new Accessor($instance);
        $this->actionData = array_merge(
            $accessor->toArray(),
            $this->actionData
        );
        
        return $return;
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