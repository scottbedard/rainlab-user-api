<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\ApiController;
use Bedard\RainLabUserApi\Classes\Utils;
use Event;
use Lang;
use Mail;
use RainLab\User\Models\Settings;
use RainLab\User\Models\User;
use Request;
use Validator;

class UsersController extends ApiController
{
    /**
     * Activate a user.
     * 
     * @param  string   $idCode
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

        return redirect(Settings::get('activate_redirect', '/'));
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
            'password' => 'required|between:4,255|confirmed',
        ];

        if ($this->loginAttribute() == Settings::LOGIN_USERNAME) {
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

        $automaticActivation = Settings::get('activate_mode') == Settings::ACTIVATE_AUTO;
        $requireActivation = Settings::get('require_activation', true);
        $userActivation = Settings::get('activate_mode') == Settings::ACTIVATE_USER;

        $user = Auth::register($data, $automaticActivation);

        Event::fire('rainlab.user.register', [$user, $data]);

        // send email if activation is performed by the user
        if ($userActivation) {
            $this->sendActivationEmail($user);
        }

        // automatically activated or not required, log the user in
        if ($automaticActivation || !$requireActivation) {
            Auth::login($user);
        }

        return $user;
    }

    /**
     * Returns true if registration is enabled.
     * 
     * @return boolean
     */
    protected function canRegister()
    {
        return Settings::get('allow_registration', true);
    }

    /**
     * Returns true if the registration is throttled.
     * 
     * @return boolean
     */
    protected function isRegisterThrottled()
    {
        if (!Settings::get('use_register_throttle', false)) {
            return false;
        }

        return User::isRegisterThrottled(Request::ip());
    }

    /**
     * Returns the login model attribute.
     * 
     * @return string
     */
    protected function loginAttribute()
    {
        return Settings::get('login_attribute', Settings::LOGIN_EMAIL);
    }
    
    /**
    * Sends the activation email to a user.
    *
    * @param  User $user
    * @return void
    */
   protected function sendActivationEmail($user)
   {
       $code = Utils::activationCode($user);
       $link = Utils::activationLink($code);

       $data = [
           'code' => $code,
           'link' => $link,
           'name' => $user->name,
       ];

       Mail::send('rainlab.user::mail.activate', $data, function($message) use ($user) {
           $message->to($user->email, $user->name);
       });
   }
}