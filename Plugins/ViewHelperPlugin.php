<?php
namespace Fwk\Core\Plugins;

use Fwk\Core\Application;
use Fwk\Core\Components\ViewHelper\EmbedViewHelper;
use Fwk\Core\Components\ViewHelper\EscapeViewHelper;
use Fwk\Core\Components\ViewHelper\ViewHelperListener;
use Fwk\Core\Components\ViewHelper\ViewHelperService;
use Fwk\Core\Components\UrlRewriter\UrlViewHelper;
use Fwk\Core\Plugin;
use Fwk\Di\ClassDefinition;
use Fwk\Di\Container;

class ViewHelperPlugin implements Plugin
{
    const SERVICE_NAME = 'viewHelper';

    /**
     * List of Default ViewHelpers
     * @var array
     */
    private $helpers = array();

    private $propName = ViewHelperService::DEFAULT_PROP_NAME;

    private $throwExceptions = true;

    /**
     */
    public function __construct(array $helpers = null, $propName = null, $throwExceptions = true)
    {
        if (null === $helpers) {
            $helpers = $this->getDefaultViewHelpers();
        }

        if (null !== $propName) {
            $this->propName = $propName;
        }

        $this->throwExceptions = $throwExceptions;
        $this->helpers = $helpers;
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
        $definition = new ClassDefinition('\Fwk\Core\Components\ViewHelper\ViewHelperService', array(
            $this->propName,
            $this->throwExceptions
        ), true);

        foreach ($this->helpers as $name => $helper) {
            $definition->addMethodCall('add', array($name, $helper));
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
        $app->addListener(new ViewHelperListener(self::SERVICE_NAME));
    }

    protected function getDefaultViewHelpers()
    {
        return array(
            'url'       => new UrlViewHelper(RequestMatcherPlugin::SERVICE_NAME, UrlRewriterPlugin::SERVICE_NAME),
            'embed'     => new EmbedViewHelper(),
            'escape'    => new EscapeViewHelper()
        );
    }
}