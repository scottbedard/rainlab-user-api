<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Bedard\RainLabUserApi\Classes\Utils;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Config;
use Event;
use Mail;
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
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];

        Event::listen('rainlab.user.beforeRegister', function ($data) use (&$beforeRegisterFired, $params) {
            $beforeRegisterFired = true;
            $this->assertArraySubset($params, $data);
        });

        Event::listen('rainlab.user.register', function ($user, $data) use(&$beforeRegisterFired, &$registerFired, $params) {
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
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422);
    }

    public function test_registering_while_registration_is_disabled()
    {
        Settings::set('allow_registration', false);

        $response = $this->post('/api/rainlab/user/users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(503);
    }

    public function test_registering_with_user_activation()
    {
        $sent = false;

        Settings::set('activate_mode', Settings::ACTIVATE_USER);
        // Settings::set('activate_url', 'https://example.com/activate/{code}');

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
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $this->assertTrue($sent);
    }

    public function test_registration_throttling()
    {
        Settings::set('use_register_throttle', false);

        $this->post('/api/rainlab/user/users', [
            'email' => 'one@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        $this->post('/api/rainlab/user/users', [
            'email' => 'two@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        $this->post('/api/rainlab/user/users', [
            'email' => 'three@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        $this->post('/api/rainlab/user/users', [
            'email' => 'four@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(200);

        Settings::set('use_register_throttle', true);

        $this->post('/api/rainlab/user/users', [
            'email' => 'five@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertStatus(429);

        $this->assertEquals(4, User::count());
    }

    public function test_registering_without_password_confirmation()
    {
        $response = $this->post('/api/rainlab/user/users', [
            'email' => 'sally@example.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(1, User::count());
        $this->assertEquals('sally@example.com', $data['email']);
    }
    
    //
    // activate
    //
    public function test_activating_a_user()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_USER);
        Settings::set('activate_redirect', '/welcome');
        
        $this->post('/api/rainlab/user/users', [
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        $user = User::first();
        $code = implode('!', [$user->id, $user->activation_code]);

        $request = $this->get('/api/rainlab/user/users/activate/' . $code);
        $request->assertRedirect('/welcome');
    }

    public function test_activating_with_invalid_code()
    {
        Settings::set('activate_mode', Settings::ACTIVATE_USER);

        $request = $this->get('/api/rainlab/user/users/activate/badcode');

        $request->assertStatus(400);
    }

    // read
    // update
    // delete
}