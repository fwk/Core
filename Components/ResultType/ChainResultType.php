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
 * @link       http://www.fwk.pw
 */
namespace Fwk\Core\Components\ResultType;

use Fwk\Core\Components\ResultType\ResultType,
    Symfony\Component\HttpFoundation\Response;
use Fwk\Core\Application, Fwk\Core\Context;
use Fwk\Core\ContextAware;

class ChainResultType implements ResultType, ApplicationAware, ContextAware
{
    protected $params;
    
    /**
     * @var Application
     */
    protected $application;
    
    /**
     *
     * @var Context
     */
    protected $context;
    
    /**
     *
     * @param array $actionData
     * @param array $params
     * 
     * @return Response
     */
    public function getResponse(array $actionData = array(), 
        array $params = array()
    ) {
        $actionName = (isset($params['actionName']) ?  
            $params['actionName'] : 
            null
        );
        
        unset($params['actionName']);
        if (null === $actionName) {
            throw new Exception('Missing ResultType parameter "actionName"');
        }
        
        $this->context->setActionName($actionName);
        foreach ($params as $key => $value) {
            if (!empty($value) 
                && strpos($value, ':', 0) !== false
                && array_key_exists(substr($value, 1), $actionData)
            ) {
                $value = $actionData[substr($value, 1)];
            }
            
            $this->context->getRequest()->query->set($key, $value);
        }
        
        return $this->application->runAction($this->context);
    }
    
    /**
     *
     * @return Application
     * @see ApplicationAware
     */
    public function getApplication()
    {
        return $this->application;
    }
    
    /**
     *
     * @param Application $app The running Application
     * 
     * @return void
     * @see ApplicationAware
     */
    public function setApplication(Application $app)
    {
        $this->application = $app;
    }
    
    /**
     *
     * @return Context
     * @see ContextAware
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     *
     * @param Context $context 
     * 
     * @return void
     * @see ContextAware
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }
}