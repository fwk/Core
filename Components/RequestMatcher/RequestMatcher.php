<?php
namespace Fwk\Core\Components\RequestMatcher;

use Symfony\Component\HttpFoundation\Request;

class RequestMatcher
{
    const DEFAULT_ACTION_REGEX  =   '/^([A-Z0-9a-z_][^\.]+)\.action/';
    
    protected $actionRegex;
    
    public function __construct($actionRegex = null)
    {
        if (null === $actionRegex) {
            $actionRegex = self::DEFAULT_ACTION_REGEX;
        }
        
        $this->actionRegex = $actionRegex;
    }
    
    public function match(Request $request)
    {
        $baseUri     = $request->getBaseUrl();
        $uri         = $request->getRequestUri();

        if(!empty($baseUri) && \strpos($uri, $baseUri) === 0) {
            $uri    = \substr($uri, strlen($baseUri));
        }

        $uri         = trim($uri, '/');
        if (empty($uri)) {
            return null;
        }
        
        if (strpos($uri, '?') > 0) {
            list($uri,) = explode('?', $uri);
        }
        
        $actionName  = false;
        if (\preg_match($this->actionRegex, $uri, $matches)) {
            $actionName = $matches[1];
        }
        
        return $actionName;
    }
    
    public function reverse($actionName, array $params = array(), 
        $escapeAmp = false
    ) {
        return sprintf(
            '/%s.action%s', 
            $actionName, 
            (!count($params) ? null : '?' . http_build_query($params, '', ($escapeAmp === true ? '&amp;' : '&')))
        );
    }
}