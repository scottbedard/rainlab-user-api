<?php namespace GivingTeam\Auth\Tests\Controllers;

use GivingTeam\Auth\Tests\PluginTestCase;
use RainLab\User\Models\User;
use RainLab\User\Models\Settings as UserSettings;

class AuthControllerTest extends PluginTestCase
{
    public function test_creating_a_user()
    {
        $response = $this->post('/api/givingteam/auth', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        $response->assertStatus(200);

        $user = json_decode($response->content());
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_creating_a_user_when_registration_is_disabled()
    {
        // disable user registration for this test. this is
        // reset in the base test case's setup function
        UserSettings::set('allow_registration', false);

        $response = $this->post('/api/givingteam/auth', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        $response->assertStatus(403);

        $content = $response->getOriginalContent();
        $this->assertEquals('disabled', $content['status']);
        $this->assertEquals('rainlab.user::lang.account.registration_disabled', $content['message']);
    }

    public function test_creating_a_user_with_invalid_data()
    {
        // this should throw a validation error, because there isn't
        // a valid email address, password, confirmation, etc...
        $response = $this->post('/api/givingteam/auth', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(400);

        $content = $response->getOriginalContent();
        $this->assertEquals('validation_failed', $content['status']);
    }
}