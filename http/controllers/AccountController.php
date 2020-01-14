<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\AccountManager;
use Bedard\RainLabUserApi\Classes\ApiController;
use Input;
use Lang;
use October\Rain\Database\ModelException;
use RainLab\User\Models\Settings as UserSettings;

class AccountController extends ApiController
{
    /**
     * Delete a user's avatar.
     *
     * @return \RainLab\User\Models\User
     */
    public function deleteAvatar()
    {
        $user = Auth::getUser();

        if ($user->avatar) {
            $user->avatar->delete();
        }

        return AccountManager::getAuthenticatedUser();
    }

    /**
     * Return the authenticated user.
     *
     * @return \RainLab\User\Models\User
     */
    public function index()
    {
        return AccountManager::getAuthenticatedUser();
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
        try {
            if (Input::hasFile('avatar')) {
                $user->avatar = Input::file('avatar');
            }

            $user->fill($data);
            $user->save();

            // password has changed, reauthenticate the user
            if (array_key_exists('password', $data) && strlen($data['password'])) {
                Auth::login($user->reload(), true);
            }
        } catch (ModelException $e) {
            return response($e->getErrors()->messages(), 422);
        }

        return AccountManager::getAuthenticatedUser();
    }
}
