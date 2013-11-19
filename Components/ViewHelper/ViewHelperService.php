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
 * @link       http://www.phpfwk.com
 */
namespace Fwk\Core\Components\ViewHelper;

use Fwk\Core\Context;
use Fwk\Core\Application;

/**
 * This is the View Helper
 *
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class ViewHelperService
{
    /**
     * Name of the property where the ViewHelper should be placed
     */
    const DEFAULT_PROP_NAME = '_helper';

    /**
     * @var array
     */
    protected $helpers = array();

    /**
     * Should the viewHelper fail silently or throw exceptions?
     *
     * @var boolean
     */
    private $throwExceptions = true;

    /**
     * The current context
     *
     * @var Context
     */
    protected $context;
    
    /**
     * Property of the ViewHelperService in Action's data
     * 
     * @var string
     */
    protected $propName = self::DEFAULT_PROP_NAME;

    /**
     * The running Application
     * 
     * @var Application
     */
    protected $application;
    
    /**
     * Constructor
     * 
     * @param string  $propName        Name of the ViewHelperService property
     * @param boolean $throwExceptions Should the Service throw exceptions or fail
     * silently
     * 
     * @return void
     */
    public function __construct($propName = self::DEFAULT_PROP_NAME, 
        $throwExceptions = true
    ) {
        $this->propName         = $propName;
        $this->throwExceptions  = $throwExceptions;
    }

    /**
     *
     * @param string $name
     * @param ViewHelper $helper
     *
     * @return ViewHelperService
     */
    public function add($name, ViewHelper $helper)
    {
        $this->helpers[strtolower($name)] = $helper;
        $helper->setViewHelperService($this);

        return $this;
    }

    /**
     *
     * @param array $helpers
     *
     * @return ViewHelperService
     */
    public function addAll(array $helpers)
    {
        foreach ($helpers as $key => $helper) {
            $this->add($key, $helper);
        }

        return $this;
    }

    /**
     *
     * @param string $helperName
     *
     * @return ViewHelperService
     * @throws Exception if helper not registered
     */
    public function remove($helperName)
    {
        if (!isset($this->helpers[$helperName])) {
            throw new Exception(sprintf("Unregistered helper '%s'", $helperName));
        }
        
        unset($this->helpers[strtolower($helperName)]);

        return $this;
    }

    /**
     *
     * @param string $name
     *
     * @return ViewHelper
     * @throws Exception if helper not registered
     */
    public function helper($name)
    {
        $name = strtolower($name);
        if (!isset($this->helpers[$name])) {
            throw new Exception(sprintf("Unregistered helper '%s'", $name));
        }

        return $this->helpers[$name];
    }

    /**
     *
     * @param boolean $bool
     *
     * @return ViewHelper
     */
    public function throwExceptions($bool)
    {
        $this->throwExceptions = (bool)$bool;

        return $this;
    }

    /**
     * Tells if the service throws exceptions or fail silently
     * 
     * @return boolean
     */
    public function isThrowExceptions()
    {
        return $this->throwExceptions;
    }
    
    /**
     *
     * @param string $name
     * @param mixed $arguments
     *
     * @return mixed
     * @throws Exception (if invalid callback && throwExceptions = true)
     */
    public function __call($name, $arguments)
    {
        try {
            $helper = $this->helper($name);
        } catch(Exception $exc) {
            if ($this->throwExceptions) {
                throw $exc;
            }

            return false;
        }

        $result = false;
        try {
            $result = $helper->execute($arguments);
        } catch(\Exception $exp) {
            if ($this->throwExceptions) {
                throw new Exception(
                    'ViewHelper '. get_class($helper) .' execution failed', 
                    $exp->getCode(), 
                    $exp
                );
            }
        }
        
        return $result;
    }

    /**
     *
     * @param Context $context
     *
     * @return ViewHelper
     */
    public function setContext(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }
    
    public function getPropName()
    {
        return $this->propName;
    }

    /**
     * 
     * @param string $propName
     * 
     * @return ViewHelperService
     */
    public function setPropName($propName)
    {
        $this->propName = $propName;
        
        return $this;
    }
    
    /**
     * 
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * 
     * @param Application $application
     * 
     * @return ViewHelperService
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
        
        return $this;
    }


}