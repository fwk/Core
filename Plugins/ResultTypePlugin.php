<?php
namespace Fwk\Core\Plugins;

use Fwk\Core\Application;
use Fwk\Core\Components\ResultType\ChainResultType;
use Fwk\Core\Components\ResultType\JsonResultType;
use Fwk\Core\Components\ResultType\RedirectResultType;
use Fwk\Core\Components\ResultType\ResultTypeListener;
use Fwk\Core\Plugin;
use Fwk\Di\ClassDefinition;
use Fwk\Di\Container;

class ResultTypePlugin implements Plugin
{
    const SERVICE_NAME = 'resultType';

    /**
     * List of Default ResultTypes
     * @var array
     */
    private $types = array();

    /**
     * @param array|null $types Default ResultTypes
     */
    public function __construct(array $types = null)
    {
        if (null === $types) {
            $types = $this->getDefaultTypes();
        }

        $this->types = $types;
    }

    /**
     * Apply Plugin's services to the existing Container
     *
     * @param Container $container App's Services Container
     *
     * @return void
     */
    public function loadServices(Container $container)
    {

        $definition = new ClassDefinition('\Fwk\Core\Components\ResultType\ResultTypeService', array(), true);
        foreach ($this->types as $name => $type) {
            $definition->addMethodCall('addType', array($name, $type));
        }

        $container->set(
            self::SERVICE_NAME,
            $definition,
            true
        );
    }

    /**
     * Returns a list of Actions for this plugin
     *
     * @param Application $app The running Application
     *
     * @return void
     */
    public function load(Application $app)
    {
        $app->addListener(new ResultTypeListener(self::SERVICE_NAME));
    }

    protected function getDefaultTypes()
    {
        return array(
            'json'      => new JsonResultType(),
            'redirect'  => new RedirectResultType(array(
                'requestMatcher' => RequestMatcherPlugin::SERVICE_NAME,
                'urlRewriter' => UrlRewriterPlugin::SERVICE_NAME
            )),
            'chain'     => new ChainResultType()
        );
    }
}