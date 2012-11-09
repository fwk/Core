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
namespace Fwk\Core\Components\ViewHelper;

use Fwk\Core\Context,
    Fwk\Core\Object;

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
class ViewHelper extends Object
{
    /**
     * Name of the property where the ViewHelper should be placed
     */
    const PROP_NAME = '_helper';

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
     * Constructor
     *
     * @param Context $context
     *
     * @return void
     */
    public function __construct(Context $context = null)
    {
        $this->context = $context;
    }

    /**
     *
     * @param string $name
     * @param Helper $helper
     *
     * @return ViewHelper
     */
    public function add($name, Helper $helper)
    {
        try {
            $old = $this->helper($name);
            if (get_class($old) == get_class($helper)) {
                return $this;
            }

            if ($this->throwExceptions) {
                throw new Exception(
                    sprintf(
                        "Already registered different helper '%s'",
                        $name
                    )
                 );
            }
        } catch(Exception $exc) {
        }

        $this->helpers[strtolower($name)] = $helper;
        $helper->setViewHelper($this);

        return $this;
    }

    /**
     *
     * @param array $helpers
     *
     * @return ViewHelper
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
     * @return ViewHelper
     */
    public function remove($helperName)
    {
        unset($this->helpers[strtolower($helperName)]);

        return $this;
    }

    /**
     *
     * @param string $name
     *
     * @return Helper
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

            return null;
        }

        return $helper->execute($arguments);
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
}