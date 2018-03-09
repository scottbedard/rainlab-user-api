<?php namespace GivingTeam\Auth\Classes;

use ApplicationException;
use Auth;
use GivingTeam\Auth\Exceptions\RegistrationDisabledException;
use RainLab\User\Models\Settings as UserSettings;
use ValidationException;
use Validator;

class AccountManager
{
    public function create($data)
    {
        // ensure that registration is enabled
        if (!$this->canRegister()) {
            throw new RegistrationDisabledException;
        }

        // validate the new user
        $rules = [
            'email'    => 'required|email|between:6,255',
            'password' => 'required|between:4,255|confirmed',
        ];

        if ($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
            $rules['username'] = 'required|between:2,255';
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = Auth::register($data);

        // @todo: fire an event

        return $user;
    }

    /**
     * Determine if registration is enabled.
     * 
     * @return boolean
     */
    public function canRegister()
    {
        return UserSettings::get('allow_registration', true);
    }

    /**
     * Determine the user login attribute.
     * 
     * @return string
     */
    public function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }
}