<?php
namespace Fwk\Core\Components\UrlRewriter;

use Fwk\Core\Components\RequestMatcher\UrlViewHelper as ViewHelperBase;

class UrlViewHelper extends ViewHelperBase
{
    protected $rewriterService;
    
    public function __construct($requestMatcherServiceName, $rewriterServiceName)
    {
        parent::__construct($requestMatcherServiceName);
        $this->rewriterService = $rewriterServiceName;
    }
    
    public function execute(array $arguments)
    {
        $actionName = (isset($arguments[0]) ? $arguments[0] : false);
        $parameters = (isset($arguments[1]) ? $arguments[1] : array());
        $escapeAmp  = (isset($arguments[2]) ? (bool)$arguments[2] : false);
        $baseUrl    = $this->getViewHelperService()
                    ->getContext()
                    ->getRequest()
                    ->getBaseUrl();
        
        if (false === $actionName) {
            return (empty($baseUrl) ? '/' : $baseUrl);
        }
        
        if (empty($actionName)) {
            throw new Exception(sprintf('Empty action name'));
        }
        
        if (!is_array($parameters)) {
            throw new Exception(sprintf('Parameters should be an array'));
        }
        
        $rewriter = $this->getUrlRewriterService();
        $route    = $rewriter->reverse($actionName, $parameters, $escapeAmp);
        if (false === $route) {
            return parent::execute($arguments);
        }
        
        return $route;
    }
    
    /**
     *
     * @return UrlRewriterService
     */
    protected function getUrlRewriterService()
    {
        return $this->getViewHelperService()
            ->getApplication()
            ->getServices()
            ->get($this->rewriterService);
    }
}