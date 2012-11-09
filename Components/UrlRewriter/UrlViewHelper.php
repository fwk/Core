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

use Fwk\Core\Object,
    Fwk\Core\Components\ViewHelper\AbstractHelper,
    Fwk\Core\Context,
    Fwk\Core\Components\ViewHelper\ViewHelper;

class UrlViewHelper extends AbstractHelper
{
    /**
     * @var ViewHelper
     */
    protected $viewHelper;

    public function __construct(array $options = array())
    {
        $this->setMulti($options);
    }

    public function execute(array $arguments)
    {
        $context = $this->getViewHelper()->getContext();
        if (!$context instanceof Context) {
            return null;
        }

        $actionName = (isset($arguments[0]) ? $arguments[0] : false);
        if (false === $actionName)
        {
            return null;
        }
        $params     = ((isset($arguments[1]) && is_array($arguments[1])) ? $arguments[1] : array());

        $base       = "%s%s";
        $baseUrl    = $context->getRequest()->getBaseUrl();

        $rewriter   = $this->getViewHelper()->get('rewriter', null);
        if (!$rewriter instanceof Rewriter) {
            return sprintf(
                $base,
                \rtrim($baseUrl, '/'),
                $this->getGenericUrl($actionName, $params)
            );
        }

        $reverse    = $rewriter->reverse($actionName, $params);
        if ($reverse === false) {
            $reverse = $this->getGenericUrl($actionName, $params);
        }

        return sprintf($base, \rtrim($baseUrl, '/'), $reverse);
    }

    protected function getGenericUrl($actionName, array $params = array())
    {
        if (count($params)) {
            $paramsStr = "?";
            foreach ($params as $key => $value) {
                $paramsStr .= urlencode($key) ."=". urlencode($value);
            }
        }

        return sprintf(
            "/%s.action%s",
            $actionName,
            (isset($paramsStr) ? $paramsStr : null)
        );
    }
}