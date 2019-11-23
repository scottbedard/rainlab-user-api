<?php

namespace Bedard\RainLabUserApi\Classes;

use RainLab\User\Models\User;

class Utils
{
    /**
     * Create activation link for a user.
     * 
     * @param  string
     * 
     * @return string
     */
    public static function activationLink(string $code)
    {
        return url(config('bedard.rainlabuserapi::apiPrefix'), ['users', 'activate', $code]);
    }

    /**
     * Create activation code for a user.
     * 
     * @param  \RainLab\User\Models\User
     * 
     * @return string
     */
    public static function activationCode(User $user)
    {
        return implode('!', [$user->id, $user->getActivationCode()]);
    }
}