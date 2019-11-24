<?php

namespace Bedard\RainLabUserApi\Classes;

use Auth;
use Event;
use RainLab\User\Models\User as UserModel;

class AccountManager
{
    /**
     * Get the authenticated user.
     * 
     * @return \RainLab\User\Models\User
     */
    public static function getAuthenticatedUser()
    {
        if (!Auth::check()) {
            return null;
        }

        return self::getUser(Auth::getUser());
    }

    /**
     * Get a user with a hook for extensibility.
     * 
     * @return \RainLab\User\Models\User
     */
    public static function getUser(UserModel $model)
    {
        $user = UserModel::find($model->id);

        if ($user) {
            $user->touchLastSeen();
        }

        Event::fire('bedard.rainlabuserapi.afterGetUser', [&$user]);

        return $user;
    }
}