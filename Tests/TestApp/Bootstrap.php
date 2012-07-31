<?php

namespace TestApp;


class Bootstrap
{
    
    public function registerTest(\Fwk\Core\Application $app)
    {
        $app->set('testBootstrap', true);
    }
}