<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Auth;
use Bedard\RainLabUserApi\Classes\Utils;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\Settings;
use RainLab\User\Models\User;

class UsersControllerTest extends PluginTestCase
{
    //
    // create
    //
    public function test_registering_a_user()
    {
        $beforeRegisterFired = false;
        $registerFired = false;

        $params = [
            'email'                 => 'john@example.com',
            'name'                  => 'John Doe',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ];

        Event::listen('rainlab.user.beforeRegister', function ($data) use (&$beforeRegisterFired, $params) {
            $beforeRegisterFired = true;
            $this->assertArraySubset($params, $data);
        });

        Event::listen('rainlab.user.register', function ($user, $data) use (&$beforeRegisterFired, &$registerFired, $params) {
            $registerFired = true;
            $this->assertTrue($beforeRegisterFired);
            $this->assertInstanceOf(User::class, $user);
            $this->assertArraySubset($params, $data);
        });

        $response = $this->post('/api/rainlab/user/users', $params);
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($registerFired);
        $this->assertEquals(1, User::count());
        $this->assertEquals(User::first()->id, $data['id']);
    }

    public function test_registering_with_invalid_login_attribute()
    {
        Settings::set('login_attribute', Settings::LOGIN_USERNAME);

        $response = $this->post('/api/rainlab/user/users', [
            'email'                 => 'john@example.com',
            'name'                  => 'John Doe',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422);
    }

    public function test_registering_while_registration_is_disabled()
    {
        Settings::set('allow_registration', false);

        $response = $this->post('/api/rainlab/user/users', [
            'email'                 => 'john@example.com',
            'name'                  => 'John Doe',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(503);
    }

    public function test_registering_with_user_activation()
    {
        $sent = false;

        Settings::set('activate_mode', Settings::ACTIVATE_USER);

        Event::listen('mailer.beforeSend', function ($view, $data) use (&$sent) {
            $user = User::first();
            $expectedCode = implode('!', [$user->id, $user->activation_code]);

            $this->assertArraySubset([
                'name' => $user->name,
                'code' => $expectedCode,
                'link' => Utils::activationLink($expectedCode),
            ], $data);

            $sent = true;
        });

        $response = $this->post('/api/rainlab/user/users', [
            'email'                 => 'john@example.com',
            'name'                  => 'John Doe',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $this->assertTrue($sent);
    }

    public function test_registering_with_auto_activation()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_AUTO);

        $response = $this->post('/api/rainlab/user/users', [
            'email'                 => 'john@example.com',
            'name'                  => 'John Doe',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $data = json_decode($response->getContent(), 200);

        $this->assertTrue($data['is_activated']);
    }

    public function test_registration_throttling()
    {
        Settings::set('use_register_throttle', false);

        $this->post('/api/rainlab/user/users', [
            'email'                 => 'one@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        $this->post('/api/rainlab/user/users', [
            'email'                 => 'two@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        $this->post('/api/rainlab/user/users', [
            'email'                 => 'three@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        $this->post('/api/rainlab/user/users', [
            'email'                 => 'four@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        Settings::set('use_register_throttle', true);

        $this->post('/api/rainlab/user/users', [
            'email'                 => 'five@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(429);

        $this->assertEquals(4, User::count());
    }

    public function test_registering_without_password_confirmation()
    {
        $response = $this->post('/api/rainlab/user/users', [
            'email'    => 'sally@example.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(1, User::count());
        $this->assertEquals('sally@example.com', $data['email']);
    }

    public function test_registering_taken_email()
    {
        $user = self::createActivatedUser([
            'email' => 'foo@bar.com',
        ]);

        $response = $this->post('/api/rainlab/user/users', [
            'email'    => 'foo@bar.com',
            'password' => 'whatever',
        ]);

        $response->assertStatus(422);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('email', $data);
    }

    public function test_registering_taken_username()
    {
        Settings::set('login_attribute', Settings::LOGIN_USERNAME);

        $user = self::createActivatedUser([
            'email'    => 'one@two.com',
            'username' => 'foobar',
        ]);

        $response = $this->post('/api/rainlab/user/users', [
            'email'    => 'foo@bar.com',
            'password' => 'whatever',
            'username' => 'foobar',
        ]);

        $response->assertStatus(422);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('username', $data);
    }

    //
    // activate
    //
    public function test_activating_a_user()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_USER);
        Settings::set('activate_redirect', '/welcome');

        $this->post('/api/rainlab/user/users', [
            'email'    => 'john@example.com',
            'password' => '12345678',
        ]);

        $user = User::first();
        $code = implode('!', [$user->id, $user->activation_code]);

        $request = $this->get('/api/rainlab/user/users/activate/'.$code);
        $request->assertRedirect('/welcome');

        $this->assertEquals($user->id, Auth::getUser()->id);
    }

    public function test_activating_with_invalid_code()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_USER);

        $request = $this->get('/api/rainlab/user/users/activate/badcode');

        $request->assertStatus(400);
    }

    public function test_activating_with_code_that_is_missing_an_id()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_USER);

        $request = $this->get('/api/rainlab/user/users/activate/!badcode');

        $request->assertStatus(400);
    }

    public function test_activating_with_code_that_matches_no_user()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_USER);

        $request = $this->get('/api/rainlab/user/users/activate/1!badcode');

        $request->assertStatus(400);
    }

    //
    // forgot password
    //
    public function test_restoring_a_password()
    {
        $sent = false;

        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'name'     => 'John Doe',
            'password' => '12345678',
            'username' => 'john',
        ]);

        Event::listen('mailer.beforeSend', function ($view, $data) use (&$sent, $user) {
            $resetUser = User::find($user->id);
            $code = implode('!', [$resetUser->id, $resetUser->reset_password_code]);
            $link = str_replace('{code}', $code, Settings::get('password_reset_url'));

            $this->assertArraySubset([
                'code'     => $code,
                'link'     => $link,
                'name'     => $resetUser->name,
                'username' => $resetUser->username,
            ], $data);

            $sent = true;
        });

        $response = $this->post('/api/rainlab/user/users/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);

        $this->assertTrue($sent);
    }

    public function test_forgot_password_with_missing_email()
    {
        $response = $this->post('/api/rainlab/user/users/forgot-password');

        $response->assertStatus(422);
    }

    public function test_resetting_guest_password()
    {
        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'name'     => 'John Doe',
            'password' => '12345678',
            'username' => 'john',
        ]);

        $user->is_guest = true;
        $user->save();

        $response = $this->post('/api/rainlab/user/users/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400);
    }

    //
    // reset password
    //
    public function test_resetting_a_password()
    {
        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'name'     => 'John Doe',
            'password' => '12345678',
            'username' => 'john',
        ]);

        $code = $user->getResetPasswordCode();

        $response = $this->post('/api/rainlab/user/users/reset-password', [
            'code'     => implode('!', [$user->id, $code]),
            'password' => 'helloworld',
        ]);

        $response->assertStatus(200);

        Settings::set('login_attribute', Settings::LOGIN_EMAIL);

        $authedUser = Auth::authenticate([
            'login'    => $user->email,
            'password' => 'helloworld',
        ]);

        $this->assertEquals($user->id, $authedUser->id);
    }

    public function test_resetting_password_without_email()
    {
        $response = $this->post('/api/rainlab/user/users/reset-password');

        $response->assertStatus(422);
    }

    public function test_invalid_password_reset_code()
    {
        $response = $this->post('/api/rainlab/user/users/reset-password', [
            'code'     => 'foobar',
            'password' => 'helloworld',
        ]);

        $response->assertStatus(400);
    }

    public function test_resetting_password_for_user_that_doesnt_exist()
    {
        $response = $this->post('/api/rainlab/user/users/reset-password', [
            'code'     => '1!abc123',
            'password' => 'helloworld',
        ]);

        $response->assertStatus(400);
    }

    public function test_resetting_password_with_malformed_code()
    {
        $response = $this->post('/api/rainlab/user/users/reset-password', [
            'code'     => '1!',
            'password' => 'helloworld',
        ]);

        $response->assertStatus(400);
    }

    public function test_resetting_password_with_incorrect_code()
    {
        $user = self::createActivatedUser([
            'email'    => 'john@example.com',
            'name'     => 'John Doe',
            'password' => '12345678',
            'username' => 'john',
        ]);

        $user->getResetPasswordCode();

        $response = $this->post('/api/rainlab/user/users/reset-password', [
            'code'     => implode('!', [$user->id, 'badcode']),
            'password' => 'helloworld',
        ]);

        $response->assertStatus(400);
    }
}
