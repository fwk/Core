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

use Fwk\Events\Event;

/**
 * @category Listeners
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class ContextEvent extends Event
{
    /**
     * Running Context
     * 
     * @var Context 
     */
    protected $context;
    
    /**
     * Constructor
     * 
     * @param type $name
     * @param type $data
     * @param Application $app
     * @param Context $context 
     * 
     * @return void
     */
    public function __construct($name, $data = array(), Context $context = null) 
    {
        parent::__construct($name, $data);
        $this->context      = $context;
    }

    /**
     * 
     * @return Context 
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     *
     * @param Context $context
     * 
     * @return CoreEvent 
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
        
        return $this;
    }
    
    /**
     *
     * @param type        $name
     * @param array       $data
     * @param Context     $context
     * 
     * @return CoreEvent 
     */
    public static function factory($name, array $data = array(), 
        Context $context = null
    ) {
        $event = new self($name, $data, $context);
        return $event;
    }
}