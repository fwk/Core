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
namespace Fwk\Core\Components\ResultType\Types;

use Fwk\Core\Components\ResultType\ResultType,
    Symfony\Component\HttpFoundation\Response;

class PhpFile implements ResultType
{
    protected $params;
    
    protected $data;
    
    public function __construct(array $params = array())
    {
        $this->params = array_merge(
            array(
                'templatesDir' => null
            ),
            $params
        );
    }
    
    public function getResponse(array $actionData = array(), 
        array $params = array()
    ) {
        if(!isset($params['file']) || empty($params['file'])) {
            if (!isset($params['template'])) {
                throw new \RuntimeException(
                    sprintf('Missing template "file" parameter')
                );
            }
            
            $params['file'] = $params['template'];
        } 
        
        $file = $params['file'];
        if(!empty($this->params['templatesDir'])) {
            $file = rtrim($this->params['templatesDir'], DIRECTORY_SEPARATOR) .
                    DIRECTORY_SEPARATOR .
                    $file;
        }
        
        $this->data = $actionData;
        
        return new Response($this->loadTemplate($file));
    }
    
    /**
     * Loads contents from simple PHP template file
     *
     * @return string
     */
    protected function loadTemplate($file)
    {
        if(!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException(
                sprintf(
                    'Template file "%s" cannot be found/read.', 
                    $file
                )
            );
        }

        ob_start();

        include $file;
        
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }
    
    /**
     *
     * @param string $param
     */
    public function __get($param)
    {
        return (isset($this->data[$param]) ? $this->data[$param] : null);
    }
    
    /**
     *
     * @param string $param
     */
    public function __isset($param)
    {
        return array_key_exists($param, $this->data);
    }
}