<?php
namespace Fwk\Core\Action;

use Fwk\Core\ActionProxy;
use Fwk\Core\Exception;

class ProxyFactory
{
    /**
     * Factory Strategy to load the correct ActionProxy. A sort of "shortcut
     * utility".
     * 
     * If $callableOrShortcut is:<br />
     * <ol>
     * <li>a <i>\Closure</i> or a <i>Callable</i> will return a 
     * <i>CallableActionProxy</i>
     * <br />(ex: array($object, 'method')</li>
     * <li>a <i>string starting with '@' followed by a name WITHOUT ':' in it</i>
     * will return a <i>ServiceActionProxy</i><br />(ex: @MyServiceReference)</li>
     * <li><i>string starting with '@' followed by a name WITH ':' in it</i>
     * will return a <i>ServiceControllerActionProxy</i> 
     * <br />(ex: @MyControllerReference:methodName)</li>
     * <li><i>string WITH ':' in it</i> will return a 
     * <i>ControllerActionProxy</i><br />(ex: MyApp\\Controllers\\Home:show)</li>
     * <li>a <i>string starting with '+' followed by a path to a php file</i>
     * will return an <i>IncludeActionProxy</i><br />
     * (ex: +/path/to/myscript.php)</li>
     * </ol>
     * 
     * @param mixed $callableOrShortcut See documentation above
     * 
     * @return ActionProxy The according ActionProxy instance
     * @throws Exception   When the shortcut is incorrect
     */
    public static function factory($callableOrShortcut)
    {
        if ($callableOrShortcut instanceof \Closure 
            || is_callable($callableOrShortcut)
        ) {
            return new CallableActionProxy($callableOrShortcut);
        } elseif (is_string($callableOrShortcut)) {
            if (strpos($callableOrShortcut, '@', 0) !== false) {
                if (strpos($callableOrShortcut, ':') === false) {
                    return new ServiceActionProxy(substr($callableOrShortcut, 1));
                } elseif (strpos($callableOrShortcut, ':') !== false) {
                    list($service, $method) = explode(':', $callableOrShortcut);
                    return new ServiceControllerActionProxy(substr($service, 1), $method);
                }
                throw new Exception(
                    sprintf(
                        'Invalid ActionProxy shortcut: '. $callableOrShortcut
                    )
                );
            } elseif (strpos($callableOrShortcut, ':') !== false) {
                list($className, $method) = explode(':', $callableOrShortcut);
                return new ControllerActionProxy($className, $method);
            } elseif (strpos($callableOrShortcut, '+', 0) !== false) {
                return new IncludeActionProxy(substr($callableOrShortcut, 1));
            }
            
            throw new Exception(
                sprintf('Invalid ActionProxy shortcut: '. $callableOrShortcut)
            );
        }
        
        throw new Exception(sprintf('Incorrect ActionProxy shortcut'));
    }
}
