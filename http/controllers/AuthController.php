<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\AccountManager;
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
     * @return \RainLab\User\Models\User
     */
    public function login()
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

        $remember = false;
        $rememberMode = Utils::rememberLoginMode();

        if ($rememberMode === UserSettings::REMEMBER_ALWAYS) {
            $remember = true;
        } elseif ($rememberMode === UserSettings::REMEMBER_ASK) {
            $remember = (bool) array_get($data, 'remember', false);
        }

        Event::fire('rainlab.user.beforeAuthenticate', [$this, $credentials]);

        $user = Auth::authenticate($credentials, $remember);

        // check if user is banned
        if ($user->isBanned()) {
            Auth::logout();

            return response(Lang::get('rainlab.user::lang.account.banned'), 405);
        }

        // record ip address
        if ($ipAddress = Request::ip()) {
            $user->touchIpAddress($ipAddress);
        }

        return AccountManager::getAuthenticatedUser();
    }

    /**
     * Log a user out.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        $user = Auth::getUser();

        Auth::logout();

        if ($user) {
            Event::fire('rainlab.user.logout', [$user]);
        }

        return response('Success', 200);
    }

    /**
     * Stop impersonating a user.
     *
     * @return \Illuminate\Http\Response
     */
    public function stopImpersonation()
    {
        if (Auth::isImpersonator()) {
            Auth::stopImpersonate();
        } else {
            Auth::logout();
        }

        return response('Success', 200);
    }
}
