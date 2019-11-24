<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\ApiController;
use Bedard\RainLabUserApi\Classes\Utils;
use Event;
use Lang;
use RainLab\User\Models\Settings as UserSettings;
use Request;
use Validator;

class AuthController extends ApiController
{

    /**
     * Authenticate a user.
     * 
     * @return Illuminate\Http\Response
     */
    public function signin()
    {
        // validate the input
        $data = post();

        $rules = [];

        $rules['login'] = Utils::loginAttribute() == UserSettings::LOGIN_USERNAME
            ? 'required|between:2,255'
            : 'required|email|between:6,255';

        $rules['password'] = 'required|between:4,255';

        if (!array_key_exists('login', $data)) {
            $data['login'] = post('username', post('email'));
        }
        
        $data['login'] = trim($data['login']);

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            return response($validation->messages(), 422);
        }

        // authenticate the user
        $credentials = [
            'login'    => array_get($data, 'login'),
            'password' => array_get($data, 'password'),
        ];
    
        switch (Utils::rememberLoginMode()) {
            case UserSettings::REMEMBER_ALWAYS:
                $remember = true;
                break;
            case UserSettings::REMEMBER_NEVER:
                $remember = false;
                break;
            case UserSettings::REMEMBER_ASK:
                $remember = (bool) array_get($data, 'remember', false);
                break;
        }

        Event::fire('rainlab.user.beforeAuthenticate', [$this, $credentials]);

        $user = Auth::authenticate($credentials, $remember);

        // check if user is banned
        if ($user->isBanned()) {
            Auth::logout();

            return response(Lang::get('rainlab.user::lang.account.banned', 405));
        }

        // record ip address
        if ($ipAddress = Request::ip()) {
            $user->touchIpAddress($ipAddress);
        }

        return $user;
    }
}