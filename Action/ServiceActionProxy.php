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
 * ActionProxy to a Service. Allows a developper to use a Service as an Action.
 *
 * @category ActionProxy
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.fwk.pw
 */
class ServiceActionProxy implements ActionProxy
{
    /**
     * Service name
     * 
     * @var string
     */
    protected $serviceName;
    
    /**
     *
     * @var array
     */
    protected $actionData = array();
    
    /**
     * Constructor
     * 
     * @param string $serviceName Service name
     * 
     * @return void
     * @throws \InvalidArgumentException if $serviceName is empty
     */
    public function __construct($serviceName)
    {
        if (empty($serviceName)) {
            throw new \InvalidArgumentException(
                "You must specify a Service Name"
            );
        }
        
        $this->serviceName = $serviceName;
    }
    
    /**
     * Executes the service and eventually return the result (if any) or an
     * instance.
     *  
     * @param Application $app     The running Application
     * @param Context     $context Actual context
     * 
     * @return mixed
     */
    public function execute(Application $app, Context $context)
    {
        $result = $app->getServices()->get($this->serviceName);
        
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