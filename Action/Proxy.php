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
namespace Fwk\Core\Action;

use Fwk\Core\Accessor,
    Fwk\Core\Context,
    Symfony\Component\HttpFoundation\Request,
    Fwk\Core\Preparable,
    Fwk\Core\Exceptions as Exceptions;

/**
 * Action Proxy
 *
 * Redirects an action name to a class instance
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class Proxy
{
    /**
     * Action name
     *
     * @var array
     */
    protected $name;

    /**
     * Action's method name
     *
     * @var string
     */
    protected $method;

    /**
     * Action's class name
     *
     * @var string
     */
    protected $class;

    /**
     *
     * @var mixed
     */
    protected $instance;

    /**
     * Actual Context
     *
     * @var Context
     */
    protected $context;

    /**
     * Constructor
     *
     * Action description should be:
     * <code>
     *     array(
     *          'class' => \BundleNs\Action\Class,
     *          'method' => 'show',
     *      )
     * </code>
     *
     * @param array $actionDesc Array description of selected action
     *
     * @return void
     */
    public function __construct($name, array $actionDesc = array())
    {
        $this->name     = $name;
        $this->class    = $actionDesc['class'];
        $this->method   = $actionDesc['method'];
    }

    /**
     * Loads the action's class
     *
     * @return mixed
     */
    public function getInstance()
    {
        if (!isset($this->instance)) {
            $className      = $this->class;
            $class          = new $className();

            $this->populate($class, $this->context->getRequest());

            $this->instance = $class;
        }

        return $this->instance;
    }

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
        foreach($props as $key) {
            $value = $request->get($key, false);
            if(false !== $value) {
                $accessor->set($key, $value);
            }
        }
    }

    /**
     * Sets the context
     *
     * @param Context $context Actual context
     *
     * @return void
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns the actual context
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    public function getName() {
        return $this->name;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getClass() {
        return $this->class;
    }
    
    public function execute()
    {
        $action      = $this->getInstance();
        $callable    = array($action, $this->method);

        if (!\is_callable($callable)) {
            throw new \RuntimeException(
                sprintf(
                    'Invalid action callback (%s::%s()', 
                    get_class($action), 
                    $this->method
                )
            );
        }

        return call_user_func(array($action, $this->method));
    }
}
