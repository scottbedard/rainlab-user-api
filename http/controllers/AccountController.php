<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\ApiController;
use Input;
use Lang;
use October\Rain\Auth\AuthException;
use RainLab\User\Models\Settings as UserSettings;

class AccountController extends ApiController
{
    /**
     * Return the authenticated user.
     * 
     * @return \RainLab\User\Models\User
     */
    public function index()
    {
        return Auth::getUser();
    }

    /**
     * Update the authenticated user.
     * 
     * @return \RainLab\User\Models\User
     */
    public function update()
    {
        $data = post();

        $user = Auth::getUser();
        
        // check the current password if safe password updates are enabled
        $safePasswordUpdates = UserSettings::get('safe_password_updates', false);

        if (array_key_exists('password', $data) && $safePasswordUpdates) {
            if (!$user->checkHashValue('password', $data['password_current'])) {
                return response(Lang::get('rainlab.user::lang.account.invalid_current_pass'), 403);
            }
        }

        // update the user data
        if (Input::hasFile('avatar')) {
            $user->avatar = Input::file('avatar');
        }

        $user->fill($data);
        $user->save();
        
        return $user;
    }
}