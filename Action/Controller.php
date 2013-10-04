<?php
/**
 * Fwk
 *
 * Copyright (c) 2013-2014, Julien Ballestracci <julien@nitronet.org>.
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
 * @copyright 2013-2014 Julien Ballestracci <julien@nitronet.org>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://www.fwk.pw
 */
namespace Fwk\Core\Action;

use Fwk\Core\ServicesAware, 
    Fwk\Core\ContextAware,
    Fwk\Core\Context,
    Fwk\Di\Container;

/**
 * Simple utility/shortcut base class for Actions.
 *
 * @category Utilities
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.fwk.pw
 */
abstract class Controller implements ContextAware, ServicesAware
{
    /**
     * @var Container
     */
    protected $services;
    
    /**
     * @var Context 
     */
    protected $context;
    
    /**
     * Get the Services Container
     * 
     * @return Container
     */
    public function getServices()
    {
        return $this->services;
    }
    
    /**
     * Sets the Services Container
     *  
     * @param Container $container Services Container
     * 
     * @return void
     */
    public function setServices(Container $container)
    {
        $this->services = $container;
    }
    
    /**
     * Sets current context
     * 
     * @param Context $context Current context
     * 
     * @return void
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns current context
     * 
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }
}