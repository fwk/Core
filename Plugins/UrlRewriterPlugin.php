<?php
namespace Fwk\Core\Plugins;

use Fwk\Core\Application;
use Fwk\Core\Components\UrlRewriter\UrlRewriterListener;
use Fwk\Core\Plugin;
use Fwk\Di\ClassDefinition;
use Fwk\Di\Container;

class UrlRewriterPlugin implements Plugin
{
    const SERVICE_NAME = 'urlRewriter';

    /**
     * Apply Plugin's services to the existing Container
     *
     * @param Container $container App's Services Container
     *
     * @return void
     */
    public function loadServices(Container $container)
    {
        $container->set(
            self::SERVICE_NAME,
            new ClassDefinition('\Fwk\Core\Components\UrlRewriter\UrlRewriterService', array()),
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
        $app->addListener(new UrlRewriterListener(self::SERVICE_NAME));
    }
}