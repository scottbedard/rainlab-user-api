<?php namespace GivingTeam\Auth\Tests\Controllers;

use Auth;
use Event;
use Mail;
use GivingTeam\Auth\Tests\PluginTestCase;
use RainLab\User\Models\User;
use RainLab\User\Models\Settings as UserSettings;

class AuthControllerTest extends PluginTestCase
{
    public function test_creating_a_new_user()
    {
        $response = $this->post('/api/givingteam/auth/register', [
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

        $response = $this->post('/api/givingteam/auth/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        $response->assertStatus(403);

        $content = $response->getOriginalContent();
        $this->assertEquals('disabled', $content['status']);
        $this->assertEquals(trans('rainlab.user::lang.account.registration_disabled'), $content['message']);
    }

    public function test_creating_a_user_with_invalid_data()
    {
        // this should throw a validation error, because there isn't
        // a valid email address, password, confirmation, etc...
        $response = $this->post('/api/givingteam/auth/register', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(400);

        $content = $response->getOriginalContent();
        $this->assertEquals('validation_failed', $content['status']);
    }

    public function test_rainlab_user_events_are_fired()
    {
        Event::fake();

        $this->post('/api/givingteam/auth/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        Event::assertDispatched('rainlab.user.beforeRegister');
        Event::assertDispatched('rainlab.user.register');
    }

    public function test_registering_sends_an_activation_email()
    {
        // Qtodo: improve this assertion
        Mail::shouldReceive('send')->once();

        // enable activation
        UserSettings::set('activate_mode', 'user');

        $response = $this->post('/api/givingteam/auth/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);
    }

    public function test_activating_a_user()
    {
        // enable activation and set a redirect path
        UserSettings::set('activate_mode', 'user');
        UserSettings::set('activation_redirect', 'https://example.com');

        // create a user
        $this->post('/api/givingteam/auth/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        // the user should not be activated by default
        $user = User::find(1);
        $this->assertEquals(0, $user->is_activated);

        // activate the user
        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $response = $this->get('/api/givingteam/auth/activate?code=' . $code);

        // verify that the user is now activated
        $user->reload();
        $this->assertEquals(1, $user->is_activated);

        // and last, we should be redirected to the correct url
        $response->assertRedirect('https://example.com');
    }

    public function test_activating_with_an_invalid_code()
    {
        $response = $this->get('/api/givingteam/auth/activate?code=123');
        
        $response->assertStatus(400);
        $content = $response->getOriginalContent();
        $this->assertEquals('validation_failed', $content['status']);
    }
    
    public function test_fetching_the_authenticated_user()
    {
        $this->post('/api/givingteam/auth/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        $user = User::findByEmail('john@example.com');
        
        Auth::login($user);

        // load the user's avatar so we can assert that this was called.
        // our test doesn't have an avatar though, so this field will
        // be null. if we didn't have this, it would be undefined.
        Event::listen('givingteam.auth.afterGetUser', function($user) {
            $user->load('avatar');
        });

        $response = $this->get('/api/givingteam/auth/user');

        // and now we should have an avatar field.
        $this->assertArrayHasKey('avatar', $response->getOriginalContent()->toArray());
    }

    public function test_sending_a_password_reset_link()
    {
        // Qtodo: improve this assertion
        Mail::shouldReceive('send')->once();

        $this->post('/api/givingteam/auth/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => 'hello',
            'password_confirmation' => 'hello',
        ]);

        $response = $this->post('/api/givingteam/auth/send-reset-email', [
            'email' => 'john@example.com',
        ]);
    }
}