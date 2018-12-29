<?php

namespace Vuetober\RainLabUserApi\Classes;

use ApplicationException;
use Auth;
use Event;
use Vuetober\RainLabUserApi\Exceptions\InvalidResetCodeException;
use Vuetober\RainLabUserApi\Exceptions\InvalidUserException;
use Vuetober\RainLabUserApi\Exceptions\RegistrationDisabledException;
use Vuetober\RainLabUserApi\Exceptions\EmailTakenException;
use Vuetober\RainLabUserApi\Exceptions\UsernameTakenException;
use Mail;
use October\Rain\Auth\AuthException;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User;
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
     * Attempt to authenticate a user.
     * 
     * @param  array    $data
     * @return RainLab\User\Models\User
     */
    public function authenticate(array $data)
    {
        // validate the request
        $rules = [
            'password' => 'required|between:4,255'
        ];

        $rules['login'] = $this->loginAttribute() == UserSettings::LOGIN_USERNAME
            ? 'required|between:2,255' // <- username
            : 'required|email|between:6,255'; // <- email
         
        $validation = Validator::make($data, $rules);
            
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // attempt to authenticate
        $credentials = [
            'login'    => array_get($data, 'login'),
            'password' => array_get($data, 'password')
        ];

        Event::fire('rainlab.user.beforeAuthenticate', [$this, $credentials]);

        $user = Auth::authenticate($credentials, true);

        // throw an exception if the user is banned
        if ($user->isBanned()) {
            Auth::logout();
            throw new AuthException('rainlab.user::lang.account.banned');
        }

        return self::getAuthenticatedUser();
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

        // make sure the email is available
        if (
            array_key_exists('email', $data) && 
            User::whereEmail($data['email'])->exists()
        ) {
            throw new EmailTakenException;
        }

        // make sure the username is available
        if (
            array_key_exists('username', $data) &&
            User::whereUsername($data['username'])->exists()
        ) {
            throw new UsernameTakenException;
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

        $user->touchLastSeen();

        // provide a hook for extensibility
        if ($user) {
            Event::fire('givingteam.auth.afterGetUser', [&$user]);
        }

        return $user;
    }

    /**
     * Get a user by a password reset code.
     * 
     * @return RainLab\User\Models\User
     */
    public function getUserByResetCode($code)
    {
        $parts = explode('!', $code);

        if (count($parts) != 2) {
            throw new InvalidResetCodeException;
        }

        list($userId, $code) = $parts;

        return User::where('reset_password_code', $code)
            ->where('id', $userId)
            ->firstOrFail();
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
     * Get the password reset URL.
     * 
     * @return string
     */
    protected function passwordResetUrl()
    {
        return UserSettings::get('password_reset_url');
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
     * Reset a user's password.
     * 
     * @param  array    $data
     * @return void
     */
    public function resetPassword($data)
    {
        // validate the request
        $rules = [
            'code'     => 'required',
            'password' => 'required|between:4,255'
        ];

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $errorFields = [
            'code' => trans('rainlab.user::lang.account.invalid_activation_code')
        ];

        // break up the code parts
        $parts = explode('!', $data['code']);

        if (count($parts) != 2) {
            throw new ValidationException($errorFields);
        }

        list($userId, $code) = $parts;
        
        if (!strlen(trim($userId)) || !strlen(trim($code)) || !$code) {
            throw new ValidationException($errorFields);
        }

        // find the user and reset their password
        if (!$user = Auth::findUserById($userId)) {
            throw new ValidationException($errorFields);
        }

        if (!$user->attemptResetPassword($code, $data['password'])) {
            throw new ValidationException($errorFields);
        }
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
            'link' => url('api/rainlab/user/activate?code=' . $code),
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
            'link' => $this->passwordResetUrl() . '?code=' . $code,
        ];

        Mail::send('rainlab.user::mail.restore', $data, function($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });
    }

    /**
     * Sign the user out.
     * 
     * @return void
     */
    public function signout()
    {
        $user = Auth::getUser();

        Auth::logout();
        
        if ($user) {
            Event::fire('rainlab.user.logout', [$user]);
        }
    }

    /**
     * Stop impersonating a user.
     * 
     * @return void
     */
    public function stopImpersonating()
    {
        if (!Auth::isImpersonator()) {
            $this->signout();
        }

        Auth::stopImpersonate();
    }

    /**
     * Update the authenticated user.
     * 
     * @return RainLab\User\Models\User
     */
    public function update(array $data)
    {
        // make sure the user is signed in
        if (!$user = Auth::getUser()) {
            throw new AuthException;
        }

        // if the user is updating their email, make sure it's available
        if (
            array_key_exists('email', $data) &&
            $data['email'] !== $user->email &&
            User::whereEmail($data['email'])->exists()
        ) {
            throw new EmailTakenException;
        }

        // if an avatar is present in the data, attach it
        if (array_key_exists('avatar', $data)) {
            $user->avatar = $data['avatar'];
            unset ($data['avatar']);
        }

        // update the user model
        $user->fill($data);
        $user->save();

        // the password has changed, re-authenticate the user
        if (array_key_exists('password', $data)) {
            Auth::login($user->reload(), true);
        }
        
        return self::getAuthenticatedUser();
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