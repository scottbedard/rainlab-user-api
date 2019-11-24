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

        $user = UserModel::find(Auth::getUser()->id);

        if ($user) {
            $user->touchLastSeen();
            
            Event::fire('bedard.rainlabuserapi.afterGetUser', [&$user]);
        }

        return $user;
    }
}