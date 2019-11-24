<?php

namespace Bedard\RainLabUserApi\Tests;

use App;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factory;
use Mail;
use Mockery;
use PluginTestCase as BasePluginTestCase;
use RainLab\User\Models\User;
use System\Classes\PluginManager;

class PluginTestCase extends BasePluginTestCase
{
    /**
     * Helper to create activated users.
     *
     * @return \RainLab\User\Models\User
     */
    public function createActivatedUser($data = [])
    {
        $user = self::createUser($data);
        $user->is_activated = true;
        $user->activated_at = now();
        $user->save();

        return $user;
    }

    /**
     * Helper function to create and re-fetch a user. The fresh user instance
     * is necessary to prevent validation errors caused by stale password fields.
     *
     * @return \RainLab\User\Models\User
     */
    public function createUser($data = [])
    {
        return User::find(factory(User::class)->create($data)->id);
    }

    /**
     * Set up function, called before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // register model factories
        App::singleton(Factory::class, function ($app) {
            $faker = $app->make(Generator::class);

            return Factory::construct($faker, plugins_path('bedard/rainlabuserapi/factories'));
        });

        // set up plugins
        $pluginManager = PluginManager::instance();
        $pluginManager->registerAll(true);
        $pluginManager->bootAll(true);

        // disable mailer
        Mail::pretend();
    }

    /**
     * Tear down function, called after each test.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        // clean up plugins
        $pluginManager = PluginManager::instance();
        $pluginManager->unregisterAll();

        // close all mocks
        Mockery::close();
    }
}
