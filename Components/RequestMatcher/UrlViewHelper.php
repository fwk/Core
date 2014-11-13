<?php
namespace Fwk\Core\Components\RequestMatcher;

use Fwk\Core\Components\ViewHelper\ViewHelper;
use Fwk\Core\Components\ViewHelper\AbstractViewHelper;
use Fwk\Core\Components\ViewHelper\Exception;

class UrlViewHelper extends AbstractViewHelper implements ViewHelper
{
    protected $serviceName;

    public function __construct($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    public function execute(array $arguments)
    {
        $actionName = (isset($arguments[0]) ? $arguments[0] : false);
        $parameters = (isset($arguments[1]) ? $arguments[1] : array());
        $escapeAmp  = (isset($arguments[2]) ? (bool)$arguments[2] : false);
        $includeHostScheme = (isset($arguments[3]) ? (bool)$arguments[3] : false);

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

        $hostScheme = $this->getViewHelperService()->getContext()->getRequest()->getSchemeAndHttpHost();

        return ($includeHostScheme ? $hostScheme : null) . rtrim($baseUrl, '/')
        . $this->getRequestMatcher()
            ->reverse($actionName, $parameters, $escapeAmp);
    }

    /**
     *
     * @return RequestMatcher
     */
    protected function getRequestMatcher()
    {
        return $this->getViewHelperService()
            ->getApplication()
            ->getServices()
            ->get($this->serviceName);
    }
}