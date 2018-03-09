<?php namespace GivingTeam\Auth\Tests;

use App;
use Artisan;
use Illuminate\Foundation\AliasLoader;
use PluginTestCase as BasePluginTestCase;
use RainLab\User\Models\Settings as UserSettings;

class PluginTestCase extends BasePluginTestCase
{
    protected $refreshPlugins = [
        'GivingTeam.Auth',
    ];

    public function createApplication()
    {
        $app = parent::createApplication();

        require __DIR__.'/../routes.php';

        return $app;
    }

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