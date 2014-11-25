<?php
namespace Fwk\Core;

use Fwk\Di\Container;

/**
 * Interface Plugin
 *
 * A plugin is a short-hand method to provide Listeners, Actions and Services to
 * an existing Application.
 *
 * @package Fwk\Core
 */
interface Plugin
{
    /**
     * Adds Plugin's services to the existing Container
     *
     * @param Container $container App's Services Container
     *
     * @return void
     */
    public function loadServices(Container $container);

    /**
     * Adds Actions and Listeners to the Application
     *
     * @param Application $app The running Application
     *
     * @return void
     */
    public function load(Application $app);

}