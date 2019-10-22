<?php

namespace Bedard\RainLabUserApi\Tests;

use App;
use Artisan;
use Config;
use Illuminate\Foundation\AliasLoader;
use PluginTestCase as BasePluginTestCase;
use RainLab\User\Models\Settings as UserSettings;

class PluginTestCase extends BasePluginTestCase
{
    /**
     * Creates the application.
     * 
     * @return Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        Config::set('app.url', 'http://127.0.0.1:8000/');

        require __DIR__.'/../routes.php';

        return $app;
    }

    /**
     * Perform test case set up.
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // reset modified settings
        UserSettings::resetDefault();
        UserSettings::set('activate_mode', 'auto');
        UserSettings::set('allow_registration', true);

        // register the auth facade
        $alias = AliasLoader::getInstance();
        $alias->alias('Auth', 'RainLab\User\Facades\Auth');
    
        App::singleton('user.auth', function() {
            return \RainLab\User\Classes\AuthManager::instance();
        });
    }
}