<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\AccountManager;
use Bedard\RainLabUserApi\Classes\ApiController;
use Bedard\RainLabUserApi\Classes\Utils;
use Event;
use Lang;
use Mail;
use October\Rain\Database\ModelException;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as UserModel;
use Request;
use Validator;

class UsersController extends ApiController
{
    /**
     * Activate a user.
     *
     * @param string $idCode
     */
    public function activate($idCode)
    {
        // validate the id and code
        $parts = explode('!', $idCode);

        if (count($parts) !== 2) {
            return response(Lang::get('rainlab.user::lang.account.invalid_activation_code'), 400);
        }

        list($userId, $code) = $parts;

        if (!strlen(trim($userId)) || !strlen(trim($code))) {
            return response(Lang::get('rainlab.user::lang.account.invalid_activation_code'), 400);
        }

        // find and activate the user
        $user = Auth::findUserById($userId);

        if (!$user || !$user->attemptActivation($code)) {
            return response(Lang::get('rainlab.user::lang.account.invalid_activation_code'), 400);
        }

        return redirect(UserSettings::get('activate_redirect', '/'));
    }

    /**
     * Send a password reset email.
     *
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword()
    {
        // validate the request
        $rules = [
            'email' => 'required|email|between:6,255',
        ];

        $validation = Validator::make(post(), $rules);

        if ($validation->fails()) {
            return response($validation->messages(), 422);
        }

        // find the user
        $user = UserModel::findByEmail(post('email'));

        if (!$user || $user->is_guest) {
            return response(Lang::get('rainlab.user::lang.account.invalid_user'), 400);
        }

        // send the password reset email
        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);
        $link = str_replace('{code}', $code, UserSettings::get('password_reset_url'));

        $data = [
            'code'     => $code,
            'link'     => $link,
            'name'     => $user->name,
            'username' => $user->username,
        ];

        Mail::send('rainlab.user::mail.restore', $data, function ($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });

        return response('Ok', 200);
    }

    /**
     * Reset a user's password.
     *
     * @return \Illuminate\Http\Response
     */
    public function resetPassword()
    {
        // validate the request
        $rules = [
            'code'     => 'required',
            'password' => 'required|between:'.UserModel::getMinPasswordLength().',255',
        ];

        $validation = Validator::make(post(), $rules);

        if ($validation->fails()) {
            return response($validation->messages(), 422);
        }

        $error = Lang::get('rainlab.user::lang.account.invalid_activation_code');

        // break up the code parts
        $parts = explode('!', post('code'));

        if (count($parts) != 2) {
            return response($error, 400);
        }

        list($userId, $code) = $parts;

        if (!strlen(trim($userId)) || !strlen(trim($code)) || !$code) {
            return response($error, 400);
        }

        // reset the users password
        if (!$user = Auth::findUserById($userId)) {
            return response($error, 400);
        }

        if (!$user->attemptResetPassword($code, post('password'))) {
            return response($error, 400);
        }

        return response('Ok', 200);
    }

    /**
     * Create a user.
     *
     * @return \RainLab\User\Models\User
     */
    public function store()
    {
        // verify that user registration is enabled
        if (!$this->canRegister()) {
            return response(Lang::get('rainlab.user::lang.account.registration_disabled'), 503);
        }

        // check if registration is throttled
        if ($this->isRegisterThrottled()) {
            return response(Lang::get('rainlab.user::lang.account.registration_throttled'), 429);
        }

        // validate the request
        $data = post();

        if (!array_key_exists('password_confirmation', $data)) {
            $data['password_confirmation'] = post('password');
        }

        $rules = [
            'email'    => 'required|email|between:6,255',
            'password' => 'required|between:8,255|confirmed',
        ];

        if (Utils::loginAttribute() == UserSettings::LOGIN_USERNAME) {
            $rules['username'] = 'required|between:2,255';
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            return response($validation->messages(), 422);
        }

        // record the ip
        if ($ipAddress = Request::ip()) {
            $data['created_ip_address'] = $data['last_ip_address'] = $ipAddress;
        }

        // register the user
        Event::fire('rainlab.user.beforeRegister', [&$data]);

        $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
        $requireActivation = UserSettings::get('require_activation', true);
        $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;

        try {
            $user = Auth::register($data, $automaticActivation);
        } catch (ModelException $e) {
            return response($e->getErrors()->messages(), 422);
        }

        Event::fire('rainlab.user.register', [$user, $data]);

        // send email if activation is performed by the user
        if ($userActivation) {
            AccountManager::sendActivationEmail($user);
        }

        // automatically activated or not required, log the user in
        if ($automaticActivation || !$requireActivation) {
            Auth::login($user);
        }

        return AccountManager::getUser($user);
    }

    /**
     * Returns true if registration is enabled.
     *
     * @return bool
     */
    protected function canRegister()
    {
        return UserSettings::get('allow_registration', true);
    }

    /**
     * Returns true if the registration is throttled.
     *
     * @return bool
     */
    protected function isRegisterThrottled()
    {
        if (!UserSettings::get('use_register_throttle', false)) {
            return false;
        }

        return UserModel::isRegisterThrottled(Request::ip());
    }
}
