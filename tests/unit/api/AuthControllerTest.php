<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Auth;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as UserModel;

class AuthControllerTest extends PluginTestCase
{
    //
    // login
    //
    public function test_email_authentication()
    {
        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        $beforeAuthenticate = false;

        Event::listen('rainlab.user.beforeAuthenticate', function ($controller, $credentials) use (&$beforeAuthenticate, $user) {
            $this->assertArraySubset([
                'login'    => $user->email,
                'password' => '12345678',
            ], $credentials);

            $beforeAuthenticate = true;
        });

        $response = $this->post('/api/rainlab/user/auth/login', [
            'email'    => $user->email,
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

        $response = $this->post('/api/rainlab/user/auth/login', [
            'username' => $user->username,
            'password' => '12345678',
        ]);

        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);

        $this->assertEquals($user->id, $data['id']);
    }

    public function test_authenticating_as_a_banned_user()
    {
        UserSettings::set('use_throttle', false);

        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        $user->ban();

        $response = $this->post('/api/rainlab/user/auth/login', [
            'email'    => $user->email,
            'password' => '12345678',
        ]);

        $response->assertStatus(405);
    }

    public function test_authenticating_with_invalid_data()
    {
        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/login', [
            'foo' => 'bar',
        ]);

        $response->assertStatus(422);
    }

    public function test_never_remembering_authentication()
    {
        UserSettings::set('remember_login', UserSettings::REMEMBER_NEVER);

        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/login', [
            'email'    => $user->email,
            'password' => '12345678',
        ]);

        $response->assertStatus(200);
    }

    public function test_ask_remembering_authentication()
    {
        UserSettings::set('remember_login', UserSettings::REMEMBER_ASK);

        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/auth/login', [
            'email'    => $user->email,
            'password' => '12345678',
            'remember' => true,
        ]);

        $response->assertStatus(200);
    }

    //
    // signout
    //
    public function test_logging_out()
    {
        $logout = false;

        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $this->assertTrue(Auth::check());

        Event::listen('rainlab.user.logout', function ($logoutUser) use (&$logout, $user) {
            $logout = true;

            $this->assertInstanceOf(UserModel::class, $user);
            $this->assertEquals($user->id, $logoutUser->id);
        });

        $response = $this->get('/api/rainlab/user/auth/logout');

        $response->assertStatus(200);

        $this->assertFalse(Auth::check());
        $this->assertTrue($logout);
    }

    //
    // stop impersonation
    //
    public function test_stop_impersonating_without_impersonating()
    {
        $user = self::createActivatedUser([
            'email'    => 'sally@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $this->assertTrue(Auth::check());

        $response = $this->get('/api/rainlab/user/auth/stop-impersonation');
        $response->assertStatus(200);

        $this->assertFalse(Auth::check());
    }

    public function test_stopping_impersonation()
    {
        $user1 = self::createActivatedUser([
            'email'    => 'sally@example.com',
            'password' => '12345678',
        ]);

        $user2 = self::createActivatedUser([
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        // sign in as user 1, and impersonate user 2
        Auth::login($user1);
        Auth::impersonate($user2);
        $this->assertTrue(Auth::isImpersonator());

        $response = $this->get('/api/rainlab/user/auth/stop-impersonation');
        $response->assertStatus(200);

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::isImpersonator());
    }
}
