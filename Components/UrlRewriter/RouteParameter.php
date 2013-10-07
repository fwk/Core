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
namespace Fwk\Core\Components\UrlRewriter;

/**
 * Route Parameter
 * 
 * @category   Utilities
 * @package    Fwk\Core
 * @subpackage Components
 * @author     Julien Ballestracci <julien@nitronet.org>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpfwk.com
 */
class RouteParameter
{
    const DEFAULT_REGEX               = '.[^/\.]{0,}';
    
    /**
     *
     * @var string 
     */
    protected $name;

    /**
     *
     * @var boolean 
     */
    protected $required;

    /**
     *
     * @var string 
     */
    protected $regex;

    /**
     *
     * @var mixed 
     */
    protected $default;

    /**
     *
     * @var string 
     */
    protected $value;

    /**
     * 
     * @param type $name
     * @param type $default
     * @param type $regex
     * @param type $required
     * @param type $value
     * 
     * @return void
     */
    public function __construct($name, $default = null, 
        $regex = self::DEFAULT_REGEX, $required = true, $value = null
    ) {
        $this->name     = (string)$name;
        $this->default  = (string)$default;
        $this->required = (bool)$required;
        $this->regex    = $regex;
        $this->value    = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function getRegex()
    {
        return $this->regex;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getValue()
    {
        return (isset($this->value) ? $this->value : $this->default);
    }
}
