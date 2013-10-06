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
use Fwk\Core\Exception;
use Fwk\Di\Container;

/**
 * ActionProxy to a PHP file inclusion.
 * 
 * Allows the developper to use $this->getContext() and $this->getServices() 
 * inside the included file.
 *
 * @category ActionProxy
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.fwk.pw
 */
class IncludeActionProxy implements ActionProxy
{
    /**
     * Path to the to-be-included file
     * 
     * @var string
     */
    protected $file;
    
    /**
     * Services Container
     * 
     * @var Container
     */
    protected $services;
    
    /**
     * Actual Application Context
     * 
     * @var Context
     */
    protected $context;
    
    /**
     *
     * @var array
     */
    protected $actionData = array();
    
    /**
     * Constructor
     * 
     * @param string $file Path to the PHP file to be included
     * 
     * @throws \InvalidArgumentException if $file is empty
     */
    public function __construct($file)
    {
        if (empty($file)) {
            throw new \InvalidArgumentException(
                "You must specify a file to include"
            );
        }
        
        $this->file    = $file;
    }
    
    /**
     * Includes the PHP file and return the content.
     * 
     * @param Application $app     The running Application
     * @param Context     $context Actual context
     * 
     * @return mixed or void if the file doesn't end with a return statement
     * @throws Exception if the file is not readable/does not exist
     */
    public function execute(Application $app, Context $context)
    {
        if (!is_file($this->file) || !is_readable($this->file)) {
            throw new Exception(
                'Unable to include file: '. $this->file . ' (not found/readable)'
            );
        } 
        
        $this->context  = $context;
        $this->services = $app->getServices();
        
        return include $this->file;
    }
    
    /**
     * Return the Services Container
     * 
     * @return Container
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Return the actual context
     * 
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
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