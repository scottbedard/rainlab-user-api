<?php

namespace Bedard\RainLabUserApi\Tests;

use Mockery;
use PluginTestCase as BasePluginTestCase;
use System\Classes\PluginManager;

class PluginTestCase extends BasePluginTestCase
{
    /**
     * Set up function, called before each test.
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $pluginManager = PluginManager::instance();
        $pluginManager->registerAll(true);
        $pluginManager->bootAll(true);
    }

    /**
     * Tear down function, called after each test.
     * 
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $pluginManager = PluginManager::instance();
        $pluginManager->unregisterAll();

        Mockery::close();
    }
}