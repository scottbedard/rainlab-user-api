<?php

namespace Bedard\RainLabUserApi\Tests\Controllers;

use Auth;
use Carbon\Carbon;
use Event;
use Mail;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use RainLab\User\Models\User;
use RainLab\User\Models\Settings as UserSettings;

class AuthControllerTest extends PluginTestCase
{
    public function test_creating_a_new_user()
    {
        $response = $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
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

        $response = $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
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
        $response = $this->post('/api/rainlab/user/register', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(400);

        $content = $response->getOriginalContent();
        $this->assertEquals('invalid', $content['status']);
    }

    public function test_rainlab_user_events_are_fired()
    {
        Event::fake();

        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
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

        $response = $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
    }

    public function test_activating_a_user()
    {
        // enable activation and set a redirect path
        UserSettings::set('activate_mode', 'user');
        UserSettings::set('activation_redirect', 'https://example.com');

        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // the user should not be activated by default
        $user = User::find(1);
        $this->assertEquals(0, $user->is_activated);

        // activate the user
        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $response = $this->get('/api/rainlab/user/activate?code=' . $code);

        // verify that the user is now activated
        $user->reload();
        $this->assertEquals(1, $user->is_activated);

        // and last, we should be redirected to the correct url
        $response->assertRedirect('https://example.com');
    }

    public function test_activating_with_an_invalid_code()
    {
        $response = $this->get('/api/rainlab/user/activate?code=123');
        
        $response->assertStatus(400);
        $content = $response->getOriginalContent();
        $this->assertEquals('invalid', $content['status']);
    }
    
    public function test_fetching_the_authenticated_user()
    {
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $user = User::findByEmail('john@example.com');
        
        Auth::login($user);

        // load the user's avatar so we can assert that this was called.
        // our test doesn't have an avatar though, so this field will
        // be null. if we didn't have this, it would be undefined.
        Event::listen('bedard.rainlabuserapi.afterGetUser', function($user) {
            $user->load('avatar');
        });

        $response = $this->get('/api/rainlab/user/user');

        // and now we should have an avatar field.
       $this->assertArrayHasKey('avatar', $response->getOriginalContent()->toArray());
    }

    public function test_sending_a_password_reset_link()
    {
        // Qtodo: improve this assertion
        Mail::shouldReceive('send')->once();

        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response = $this->post('/api/rainlab/user/send-reset-email', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_resetting_a_users_password()
    {
        // create a user with a password of "12345678"
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $user = User::findByEmail('john@example.com');

        // reset the user's password to "whatever"
        $response = $this->post('/api/rainlab/user/reset-password', [
            'code' => implode('!', [$user->id, $user->getResetPasswordCode()]),
            'password' => 'whatever',
        ]);

        // verify that the response was successful
        $response->assertStatus(200);
        $this->assertEquals('success', $response->getOriginalContent()['status']);

        // verify that the password has been updated
        $user->reload();
        $this->assertTrue($user->checkPassword('whatever'));
    }

    public function test_authenticating_a_user()
    {
        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // manually set our created at timestamp to yesterday so we aren't
        // considered online when we log in. this will let the last_seen
        // timestamp be touched.
        $user = User::whereEmail('john@example.com')->first();
        $user->created_at = Carbon::yesterday();
        $user->save();

        // just a sanity check, we should be logged out before logging in
        Auth::logout();

        $response = $this->post('/api/rainlab/user/signin', [
            'login' => 'john@example.com',
            'password' => '12345678',
            'remember' => false,
        ]);

        // the user should have been returned, and we should now be logged in
        $this->assertEquals('john@example.com', $response->getOriginalContent()->email);
        $this->assertEquals('john@example.com', Auth::getUser()->email);
        $this->assertTrue(Auth::check());

        // user assertions
        $user = User::whereEmail('john@example.com')->first();
        $this->assertNotNull($user->last_seen);
    }

    public function test_authentication_with_incorrect_credentials()
    {
        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // just a sanity check, we should be logged out before logging in
        Auth::logout();

        $response = $this->post('/api/rainlab/user/signin', [
            'login' => 'john@example.com',
            'password' => 'wrong-password',
            'remember' => false,
        ]);

        $response->assertStatus(403);
        $this->assertEquals('failed', $response->getOriginalContent()['status']);
    }

    public function test_signing_out()
    {
        Event::fake();

        // create a user and sign in
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        Auth::login(User::findByEmail('john@example.com'));

        // quick sanity check to make sure we're actually signed in
        $this->assertTrue(Auth::check());

        // request the signout route
        $response = $this->get('/api/rainlab/user/signout');

        // it should response with a standard success resposne
        $response->assertStatus(200);
        $this->assertEquals('success', $response->getOriginalContent()['status']);

        // we should now be signed out and an event should have been fired
        $this->assertFalse(Auth::check());
        Event::assertDispatched('rainlab.user.logout');
    }
    
    public function test_updating_a_user()
    {
        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // try changing the user's name and email
        $response = $this->post('/api/rainlab/user/user', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        // the name and email should now be changed
        $user = $response->getOriginalContent();
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
    }

    public function test_getting_a_user_by_their_reset_password_code()
    {
        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // fetch the user by their reset password code
        $user = User::findByEmail('john@example.com');

        $response = $this->get('/api/rainlab/user/reset-password?code=' . $user->id . '!' . $user->getResetPasswordCode());

        $this->assertEquals('John Doe', $response->getOriginalContent()->name);
    }

    public function test_getting_a_user_by_invalid_reset_code()
    {
        $response = $this->get('/api/rainlab/user/reset-password?code=1!abc');

        $response->assertStatus(500);
    }

    public function test_updating_a_users_password()
    {
        // create a user with the password "12345678"
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // change the password to "23456789"
        $response = $this->post('/api/rainlab/user/user', [
            'password' => '23456789',
            'password_confirmation' => '23456789',
        ]);

        // the password should now be updated and the user returned
        $user = Auth::getUser();
        $this->assertTrue($user->checkPassword('23456789'));
        $this->assertEquals('john@example.com', $response->getOriginalContent()->email);
    }

    public function test_updating_user_info_while_not_authenticated()
    {
        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // make sure we're not logged in
        Auth::logout();

        $response = $this->post('/api/rainlab/user/user', [
            'password' => '23456789',
            'password_confirmation' => '23456789',
        ]);

        $response->assertStatus(403);
    }

    public function test_stopping_impersonation_of_a_user()
    {
        // create a user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // impersonate that user
        $user = User::findByEmail('john@example.com');
        Auth::impersonate($user);

        // quick sanity check to make sure we're actually impersonating someone
        $this->assertTrue(Auth::isImpersonator());

        // last, hit our endpoint and verify that we're no longer impersonating anyone
        $response = $this->get('/api/rainlab/user/stop-impersonating');
        $response->assertStatus(200);
        
        $this->assertFalse(Auth::isImpersonator());
    }

    public function test_updating_email_to_a_taken_email()
    {
        // create our first user
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // create a second user
        $this->post('/api/rainlab/user/register', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        // try to change our second user's email to the first
        $response = $this->post('/api/rainlab/user/user', [
            'email' => 'john@example.com',
        ]);

        // and we should get the following error
        $response->assertStatus(500);
        $this->assertEquals('email_taken', $response->getOriginalContent()['status']);
    }

    public function test_deleting_a_users_avatar()
    {
        // create a user with an avatar
        $this->post('/api/rainlab/user/register', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $user = Auth::getUser();
        $user->avatar()->create(['data' => plugins_path('bedard/rainlabuserapi/tests/avatar.png')]);
        
        // make sure our user has an avatar to prevent false positives
        $this->assertEquals('avatar.png', User::find($user->id)->avatar->file_name);

        // submit a request to remove the avatar
        $response = $this->delete('/api/rainlab/user/user/avatar');
        $response->assertStatus(200);
        $this->assertNull(User::with('avatar')->find($user->id)->avatar);
    }
}