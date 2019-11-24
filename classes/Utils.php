<?php

namespace Bedard\RainLabUserApi\Classes;

use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as UserModel;

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
    public static function activationCode(UserModel $user)
    {
        return implode('!', [$user->id, $user->getActivationCode()]);
    }

    /**
     * Returns the login model attribute.
     *
     * @return string
     */
    public static function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    /**
     * Returns the login remember mode.
     *
     * @return string
     */
    public static function rememberLoginMode()
    {
        return UserSettings::get('remember_login', UserSettings::REMEMBER_ALWAYS);
    }
}
