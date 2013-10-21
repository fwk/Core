<?php
namespace Fwk\Core\Components\ViewHelper;

class EmbedViewHelper extends AbstractViewHelper implements ViewHelper
{
    public function execute(array $arguments)
    {
        $actionName = (isset($arguments[0]) ? $arguments[0] : null);
        $parameters = (isset($arguments[1]) ? $arguments[1] : null);
        
        if (empty($actionName)) {
            throw new Exception(sprintf('Empty action name'));
        }
        
        if (!is_array($parameters)) {
            throw new Exception(sprintf('Parameters should be an array'));
        }
        
        $context = $this->getViewHelperService()->getContext()->newParent();
        $context->setActionName($actionName);
        
        foreach ($parameters as $key => $value) {
            $context->getRequest()->query->set($key, $value);
        }
        
        $result = $this->getViewHelperService()
                    ->getApplication()
                    ->runAction($context);
        
        if ($context->getResponse() instanceof \Symfony\Component\HttpFoundation\Response) {
            return $context->getResponse()->getContent();
        }
        
        return $result;
    }
}