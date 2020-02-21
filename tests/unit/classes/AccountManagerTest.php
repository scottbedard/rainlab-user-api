<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Classes;

use Auth;
use Bedard\RainLabUserApi\Classes\AccountManager;
use Bedard\RainLabUserApi\Classes\Utils;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\User as UserModel;

class AccountManagerTest extends PluginTestCase
{
    public function test_getting_authenticated_user_with_event_hook()
    {
        Auth::login(self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]));

        Event::listen('bedard.rainlabuserapi.afterGetUser', function (&$user) {
            $user->foo = 'bar';
        });

        $user = AccountManager::getAuthenticatedUser();

        $this->assertInstanceOf(UserModel::class, $user);
        $this->assertEquals('bar', $user->foo);
    }

    public function test_getting_authenticated_user_without_authenticating()
    {
        $user = AccountManager::getAuthenticatedUser();

        $this->assertNull($user);
    }

    public function test_sending_a_users_activation_email()
    {
        $sent = false;

        $user = $this->createUser();

        Event::listen('mailer.beforeSend', function ($view, $data) use (&$sent, $user) {
            $expectedCode = implode('!', [$user->id, $user->activation_code]);

            $this->assertArraySubset([
                'name' => $user->name,
                'code' => $expectedCode,
                'link' => Utils::activationLink($expectedCode),
            ], $data);

            $sent = true;
        });

        AccountManager::sendActivationEmail($user);

        $this->assertTrue($sent);
    }
}
