<?php
namespace Fwk\Core\Components\ResultType;

use Fwk\Core\Events\AfterActionEvent;
use Fwk\Core\Components\Descriptor\Descriptor;
use Fwk\Core\Components\Descriptor\DescriptorLoadedEvent;
use Fwk\Core\Events\BootEvent;
use Symfony\Component\HttpFoundation\Response;
use Fwk\Xml\Map, Fwk\Xml\Path;
use Fwk\Di\ClassDefinition, Fwk\Di\Container;

class ResultTypeListener
{
    protected $serviceName;
    
    /**
     *
     * @var Descriptor
     */
    protected $descriptor;
    
    public function __construct($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    public function onBoot(BootEvent $event)
    {
        $event->getApplication()->notify(
            new ResultTypeServiceLoadedEvent(
                $event->getApplication()->getServices()->get($this->serviceName),
                array(), $event->getApplication(), $event->getContext()
            )
        );
    }
    
    public function onAfterAction(AfterActionEvent $event)
    {
        $result     = $event->getContext()->getResult();
        if (!is_string($result)) {
            return;
        }
        
        $service = $event->getApplication()
                    ->getServices()
                    ->get($this->serviceName);
        
        $this->loadActionResultTypes(
            $event->getContext()->getActionName(), 
            $service
        );
        
        $response = $service->execute($result, 
            $event->getContext(), 
            $event->getApplication(),
            $event->getActionProxy()->getActionData()
        );

        if ($response instanceof Response) {
            $event->getContext()->setResponse($response);
        }
    }
    
    public function onDescriptorLoaded(DescriptorLoadedEvent $event)
    {
        $this->descriptor = $event->getDescriptor();
        $service = $event->getApplication()
                    ->getServices()
                    ->get($this->serviceName);
        
        $types  = array();
        $map    = $this->xmlResultsTypesMapFactory();
        foreach ($event->getDescriptor()->getSourcesXml() as $xml) {
            $this->descriptor->set('packageDir', dirname($xml->getRealPath()));
            $parse  = $map->execute($xml);
            $res    = (isset($parse['types']) ? $parse['types'] : array());
            $types  = array_merge($types, $res);
        }
        
        foreach ($types as $typeName => $type) {
            $def = new ClassDefinition(
                $event->getDescriptor()->propertizeString($type['class']), 
                array($type['params'])
            );
            
            $service->addType(
                $typeName, 
                $def->invoke($event->getApplication()->getServices())
            );
        }
        
        $this->descriptor->set('packageDir', null);
    }
    
    protected function loadActionResultTypes($actionName, 
        ResultTypeService $service
    ) {
        if (!$this->descriptor instanceof Descriptor) {
            return;
        }
        
        $results    = array();
        $map        = $this->xmlActionResultsXmlMapFactory($actionName);
        foreach ($this->descriptor->getSourcesXml() as $xml) {
            $this->descriptor->set('packageDir', dirname($xml->getRealPath()));
            $parse      = $map->execute($xml);
            $res        = (isset($parse['results']) ? $parse['results'] : array());
            $results    = array_merge($results, $res);
        }
        
        foreach ($results as $result => $data) {
            $service->register($actionName, 
                $result, 
                $data['type'], 
                $data['params']
            );
        }
        
        $this->descriptor->set('packageDir', null);
    }
    
    /**
     *
     * @return Map
     */
    protected function xmlResultsTypesMapFactory()
    {
        $map = new Map();
        $map->add(
            Path::factory('/fwk/result-types/result-type', 'types')
            ->loop(true, '@name')
            ->attribute('class')
            ->addChildren(
                 Path::factory('param', 'params')
                ->filter(array($this->descriptor, 'propertizeString'))
                ->loop(true, '@name')
            )
        );

        return $map;
    }

    /**
     *
     * @return Map
     */
    protected function xmlActionResultsXmlMapFactory($actionName)
    {
        $map = new Map();
        $map->add(
            Path::factory(
                sprintf("/fwk/actions/action[@name='%s']/result", $actionName),
                'results'
            )
            ->loop(true, '@name')
            ->attribute('type')
            ->addChildren(
                 Path::factory('param', 'params')
                ->filter(array($this->descriptor, 'propertizeString'))
                ->loop(true, '@name')
            )
        );

        return $map;
    }
}