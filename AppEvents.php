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

/**
 * Static class grouping Application events
 * 
 * @category Utils
 * @package  Fwk\Core
 * @author   Julien Ballestracci <julien@nitronet.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://www.phpfwk.com
 */
class AppEvents
{
    /**
     * Event: notified on app bootstrap
     */
    const BOOT           = 'boot';

    /**
     * Event: notified after app bootstrap
     */
    const REQUEST        = 'request';
    /**
     * Event: notified when app needs dispatch
     */
    const DISPATCH       = 'dispatch';
  
    /**
     * notified when routing/dispatch has set context in error state
     */
    const CONTEXT_ERROR  = 'contextError';
    /**
     * notified when app is ready to load action class
     */
    const INIT           = 'init';
    /**
     * notified when action class is loaded
     */
    const ACTION_LOADED  = 'actionLoaded';
    /**
     * notified when action class is executed successfuly
     */
    const ACTION_SUCCESS = 'actionSuccess';
    /**
     * notified when result is set
     */
    const RESULT         = 'result';
    /**
     * notified when response is set
     */
    const RESPONSE       = 'response';
    /**
     * triggered just before sending the final response
     */
    const FINAL_RESPONSE = 'finalResponse';
    /**
     * notified when the response is sent to the client
     */
    const END            = 'end';
    /**
     *  notified when the response is sent to the client
     */
    const ERROR          = 'error';
}