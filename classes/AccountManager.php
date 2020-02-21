<?php

namespace Bedard\RainLabUserApi\Classes;

use Auth;
use Event;
use Mail;
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
            return;
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

    /**
     * Sends the activation email to a user.
     *
     * @param User $user
     *
     * @return void
     */
    public static function sendActivationEmail(UserModel $user)
    {
        $code = Utils::activationCode($user);

        $link = Utils::activationLink($code);

        $data = [
            'code' => $code,
            'link' => $link,
            'name' => $user->name,
        ];

        Mail::send('rainlab.user::mail.activate', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }
}
