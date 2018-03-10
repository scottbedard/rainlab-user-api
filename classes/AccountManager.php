<?php namespace GivingTeam\Auth\Classes;

use ApplicationException;
use Auth;
use Event;
use GivingTeam\Auth\Exceptions\InvalidUserException;
use GivingTeam\Auth\Exceptions\RegistrationDisabledException;
use Mail;
use RainLab\User\Models\User;
use RainLab\User\Models\Settings as UserSettings;
use ValidationException;
use Validator;

class AccountManager
{
    /**
     * Activate a user.
     * 
     * @param  string   $code
     * @return void
     */
    public function activate($code)
    {
        // parse and validate the activation code
        $errorFields = [
            'code' => trans('rainlab.user::lang.account.invalid_activation_code'),
        ];

        $parts = explode('!', $code);

        if (count($parts) != 2) {
            throw new ValidationException($errorFields);
        }

        list($userId, $code) = $parts;

        if (!strlen(trim($userId)) || !strlen(trim($code))) {
            throw new ValidationException($errorFields);
        }

        // find and activate the user
        if (!$user = Auth::findUserById($userId)) {
            throw new ValidationException($errorFields);
        }

        if (!$user->attemptActivation($code)) {
            throw new ValidationException($errorFields);
        }

        // sign the user in
        Auth::login($user);
    }

    /**
     * Determine if users should be auto-activated.
     * 
     * @return boolean
     */
    protected function automaticActivation()
    {
        return UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
    }

    /**
     * Determine if registration is enabled.
     * 
     * @return boolean
     */
    protected function canRegister()
    {
        return UserSettings::get('allow_registration', true);
    }

    /**
     * Create an account.
     * 
     * @return RainLab\User\Models\User
     */
    public function create($data)
    {
        // ensure that registration is enabled
        if (!$this->canRegister()) {
            throw new RegistrationDisabledException;
        }

        // validate the request
        $rules = [
            'email' => 'required|email|between:6,255',
            'password' => 'required|between:4,255|confirmed',
        ];

        if ($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
            $rules['username'] = 'required|between:2,255';
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // create the new account
        Event::fire('rainlab.user.beforeRegister', [&$data]);

        $user = Auth::register($data, $this->automaticActivation());

        Event::fire('rainlab.user.register', [$user, $data]);

        // send an activation email if necessary
        if ($this->userActivation()) {
            $this->sendActivationEmail($user);
        }

        // sign the user in if they were automatically 
        // activated, or require no activation
        if ($this->automaticActivation() || !$this->requireActivation()) {
            Auth::login($user);
        }
        
        // and finally, return the user
        return $user;
    }

    /**
     * Get the authenticated user.
     * 
     * @return RainLab\User\Models\User
     */
    public function getAuthenticatedUser()
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::getUser();

        // provide a hook for extensibility
        if ($user) {
            Event::fire('givingteam.auth.afterGetUser', [&$user]);
        }

        return $user;
    }

    /**
     * Determine the user login attribute.
     * 
     * @return boolean
     */
    protected function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    /**
     * Determine if activation is required.
     * 
     * @return boolean
     */
    protected function requireActivation()
    {
        return UserSettings::get('require_activation', true);
    }

    /**
     * Send an activation email to a user.
     * 
     * @param  RainLab\User\Models\User
     */
    protected function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);

        $data = [
            'name' => $user->name,
            'link' => url('api/givingteam/auth/activate?code=' . $code),
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }

    /**
     * Send the user a password reset link.
     * 
     * @param  string   $email
     * @return void
     */
    public function sendResetEmail($email)
    {

        // validate the request
        $data = [
            'email' => $email,
        ];

        $rules = [
            'email' => 'required|email|between:6,255'
        ];

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // find the user
        $user = User::findByEmail($email);
        
        if (!$user || $user->is_guest) {
            throw new InvalidUserException;
        }

        // and finally, send them a reset link
        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);

        $data = [
            'name' => $user->name,
            'code' => $code,
        ];

        Mail::send('rainlab.user::mail.restore', $data, function($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });
    }

    /**
     * Determine if the user should receive an activation email.
     * 
     * @return boolean
     */
    protected function userActivation()
    {
        return UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
    }
}