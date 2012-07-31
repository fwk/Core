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
namespace Fwk\Core\Components\UrlRewriter;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use Fwk\Core\ViewHelper\Helpers\Url;

class RwViewHelperListener {

    const FUNC_NAME = Url::FUNC_NAME;

    protected $rewriter;

    public function __construct(\Fwk\Core\UrlRewriter\Rewriter $rewriter) {
        $this->rewriter = $rewriter;
    }
    
    public function onFunctionCall($event) {
        if($event->funcName !== self::FUNC_NAME)
                return;

        $actionName = (isset($event->arguments[0]) ? $event->arguments[0] : false);
        $params     = ((isset($event->arguments[1]) && is_array($event->arguments[1])) ? $event->arguments[1] : array());

        if(false === $actionName)
        {
            return;
        }

        $base       = "%s%s";
        $context    = $event->context;
        $baseUrl    = "/";

        if($context instanceof \Fwk\Action\Context) {
            $request    = $context->getRequest();
            if($request instanceof \Fwk\Request\HttpRequest)
                $baseUrl = $request->getBaseUri () ."/";
        }
        
        $reverse    = $this->rewriter->reverse($actionName, $params);
        if($reverse === false)
            return;
        
        $str    = sprintf($base, \rtrim($baseUrl,'/'), $reverse);
        $event->returnValue = $str;
    }

}