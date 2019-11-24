<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Auth;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\Settings as UserSettings;

class AuthControllerTest extends PluginTestCase
{
    //
    // signin
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

    public function test_authenticating_as_a_banned_user()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        $user->ban();

        $response = $this->post('/api/rainlab/user/auth/signin', [
            'email' => $user->email,
            'password' => '12345678',
        ]);

        // ideally this would be a 405 response
        // see: https://github.com/rainlab/user-plugin/issues/413
        $response->assertStatus(500);
    }

    public function test_authenticating_with_invalid_data()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/signin', [
            'foo' => 'bar',
        ]);

        $response->assertStatus(422);
    }

    public function test_never_remembering_authentication()
    {
        UserSettings::set('remember_login', UserSettings::REMEMBER_NEVER);

        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/signin', [
            'email' => $user->email,
            'password' => '12345678',
        ]);

        $response->assertStatus(200);
    }

    public function test_ask_remembering_authentication()
    {
        UserSettings::set('remember_login', UserSettings::REMEMBER_ASK);

        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/signin', [
            'email' => $user->email,
            'password' => '12345678',
            'remember' => true,
        ]);

        $response->assertStatus(200);
    }

    //
    // signout
    //
    public function test_signing_out()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);
        
        $this->assertTrue(Auth::check());

        $response = $this->get('/api/rainlab/user/auth/signout');
        
        $response->assertStatus(200);
        
        $this->assertFalse(Auth::check());
    }
}
