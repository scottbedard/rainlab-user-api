<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Auth;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\Settings as UserSettings;

class AuthControllerTest extends PluginTestCase
{
    //
    // authenticate
    //
    public function test_email_authentication()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        $beforeAuthenticate = false;
        
        Event::listen('rainlab.user.beforeAuthenticate', function ($controller, $credentials) use (&$beforeAuthenticate, $user) {
            $this->assertArraySubset([
                'login' => $user->email,
                'password' => '12345678',
            ], $credentials);

            $beforeAuthenticate = true;
        });

        $response = $this->post('/api/rainlab/user/auth/signin', [
            'email' => $user->email,
            'password' => '12345678',
        ]);

        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($beforeAuthenticate);
        $this->assertEquals($user->id, $data['id']);
    }

    public function test_username_authentication()
    {
        UserSettings::set('login_attribute', UserSettings::LOGIN_USERNAME);

        $user = self::createActivatedUser([
            'username' => 'foobar',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/signin', [
            'username' => $user->username,
            'password' => '12345678',
        ]);

        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);

        $this->assertEquals($user->id, $data['id']);
    }
}
