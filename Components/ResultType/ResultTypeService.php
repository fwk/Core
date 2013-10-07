<?php
namespace Fwk\Core\Components\ResultType;

use Fwk\Core\Context;

class ResultTypeService
{
    /**
     * List of ResultType's 
     * 
     * @var array
     */
    protected $types = array();
    
    /**
     * Ruleset for actions/results/type/params
     * 
     * @var array
     */
    protected $rules = array();
    
    /**
     * Tells the service to use that ResultType ($typeName) when the Action
     * ($action) returns $result, using $parameters.
     * 
     * @param string $actionName The Action name
     * @param string $result The Result
     * @param string $typeName The ResultType to use
     * @param array $parameters Eventual parameters to use along with the ResultType
     * 
     * @return ResultTypeService 
     * @throws Exception if trying to use an unregistered type
     */
    public function register($actionName, $result, $typeName, 
        array $parameters = array()
    ) {
        if (!$this->hasType($typeName)) {
            throw new Exception(sprintf('Unregistered type: "%s"', $typeName));
        }
        
        if (!isset($this->rules[$actionName])) {
            $this->rules[$actionName] = array();
        }
        
        $this->rules[$actionName][$result] = array(
            'typeName'      => $typeName,
            'parameters'    => $parameters
        );
        
        return $this;
    }
    
    /**
     *
     * @param string $result
     * @param Context $context
     * 
     * @return array Rule array or false if not found
     */
    protected function find($result, Context $context)
    {
        $actionName = $context->getActionName();
        if (!isset($this->rules[$actionName])) {
            return false;
        }
        
        if ($context->getRequest()->isXmlHttpRequest() 
            && isset($this->rules[$actionName]['ajax:'. $result])
        ) {
            return $this->rules[$actionName]['ajax:'. $result];
        } elseif (isset($this->rules[$actionName][$result])) {
            return $this->rules[$actionName][$result];
        }
        
        return false;
    }
    
    /**
     *
     * @param string $result
     * @param Context $context
     * @param array $actionData
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws Exception if no ResultType found for this result
     */
    public function execute($result, Context $context, 
        array $actionData = array()
    ) {
        $rule = $this->find($result, $context);
        if (false === $rule) {
            throw new Exception(
                sprintf('No ResultType found for result:'. $result)
            );
        }
        
        return $this->getType($rule['typeName'])
                ->getResponse($actionData, $rule['parameters']);
    }
    
    public function addType($typeName, ResultType $type)
    {
        $this->types[$typeName] = $type;
        
        return $this;
    }
    
    public function removeType($typeName)
    {
        if (array_key_exists($typeName, $this->types)) {
            unset($this->types[$typeName]);
        }
        
        return $this;
    }
    
    /**
     *
     * @param string $typeName
     * 
     * @return boolean
     */
    public function hasType($typeName)
    {
        return array_key_exists($typeName, $this->types);
    }
    
    /**
     *
     * @param string $typeName
     * 
     * @return ResultType
     * @throws Fwk\Core\Components\ResultType\Exception if unknown type
     */
    public function getType($typeName)
    {
        if (!array_key_exists($typeName, $this->types)) {
            throw new Exception(sprintf('Unknown type: "%s"', $typeName));
        }
        
        return $this->types[$typeName];
    }
}