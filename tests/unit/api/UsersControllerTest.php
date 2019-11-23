<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use Mockery;
use RainLab\User\Models\Settings;
use RainLab\User\Models\User;

class UsersControllerTest extends PluginTestCase
{
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

    public function test_creating_a_user_while_registration_is_disabled()
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

    // create (throttled)
    
    // read
    // update
    // delete
}