<?php

namespace Bedard\RainLabUserApi\Tests\Unit\Api;

use Auth;
use Bedard\RainLabUserApi\Tests\PluginTestCase;
use Event;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as UserModel;

class AccountControllerTest extends PluginTestCase
{
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
}