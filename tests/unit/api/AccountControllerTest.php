<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Auth;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as UserModel;

class AccountControllerTest extends PluginTestCase
{
    //
    // read
    //
    public function test_getting_user_information()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $response = $this->get('/api/rainlab/user/account');
        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($user->id, $data['id']);
    }

    //
    // update
    //
    public function test_updating_user_information()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $response = $this->post('/api/rainlab/user/account', [
            'email' => 'whatever@foo.com',
        ]);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($user->id, $data['id']);

        $freshUser = UserModel::find($user->id);
        $this->assertEquals('whatever@foo.com', $freshUser->email);
    }

    public function test_unsafe_password_change()
    {
        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $response = $this->post('/api/rainlab/user/account', [
            'password' => 'helloworld',
            'password_confirmation' => 'helloworld',
        ]);

        $auth = Auth::authenticate([
            'login' => 'john@example.com',
            'password' => 'helloworld',
        ]);

        $this->assertInstanceOf(UserModel::class, $auth);
    }

    public function test_valid_safe_password_change()
    {
        UserSettings::set('safe_password_updates', true);

        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $response = $this->post('/api/rainlab/user/account', [
            'password' => 'helloworld',
            'password_confirmation' => 'helloworld',
            'password_current' => '12345678',
        ]);

        $auth = Auth::authenticate([
            'login' => 'john@example.com',
            'password' => 'helloworld',
        ]);

        $this->assertInstanceOf(UserModel::class, $auth);
    }

    public function test_invalid_safe_password_change()
    {
        UserSettings::set('safe_password_updates', true);

        $user = self::createActivatedUser([
            'email' => 'john@example.com',
            'password' => '12345678',
        ]);

        Auth::login($user);

        $response = $this->post('/api/rainlab/user/account', [
            'password' => 'helloworld',
            'password_confirmation' => 'helloworld',
            'password_current' => 'wrongpassword',
        ]);
        
        $response->assertStatus(403);
    }
}