<?php
namespace Fwk\Core\Plugins;

use Fwk\Core\Application;
use Fwk\Core\Components\RequestMatcher\RequestMatcherListener;
use Fwk\Core\Plugin;
use Fwk\Di\ClassDefinition;
use Fwk\Di\Container;

class RequestMatcherPlugin implements Plugin
{
    const SERVICE_NAME = 'requestMatcher';

    /**
     * Regex used to determine Action name
     *
     * @var null|string
     */
    private $actionRegex;

    /**
     * Constructor
     *
     * @param string|null $actionRegex Regex used to determine Action name
     *
     * @return void
     */
    function __construct($actionRegex = null)
    {
        $this->actionRegex = $actionRegex;
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
        $container->set(
            self::SERVICE_NAME,
            new ClassDefinition('\Fwk\Core\Components\RequestMatcher\RequestMatcher',
                array(
                    $this->actionRegex
                )
            ),
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
        $app->addListener(new RequestMatcherListener(self::SERVICE_NAME));
    }
}