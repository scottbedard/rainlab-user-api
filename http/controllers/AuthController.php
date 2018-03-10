<?php namespace GivingTeam\Auth\Http\Controllers;

use ApplicationException;
use Auth;
use Exception;
use GivingTeam\Auth\Classes\AccountManager;
use GivingTeam\Auth\Exceptions\InvalidUserException;
use GivingTeam\Auth\Exceptions\RegistrationDisabledException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use October\Rain\Auth\AuthException;
use RainLab\User\Models\Settings as UserSettings;
use ValidationException;

class AuthController extends Controller
{

    /**
     * Activate the user.
     * 
     * @return \Illuminate\Http\Response
     */
    public function activate(AccountManager $manager)
    {
        // attempt to activate the user
        try {
            $manager->activate(input('code'));
        }

        // invalid activation code
        catch (ValidationException $e) {
            return response([
                'status' => 'validation_failed',
                'message' => $e->getMessage(),
            ], 400);
        }

        return redirect(UserSettings::get('activation_redirect', url()));
    }

    /**
     * Attempt to authenticate a user.
     * 
     * @return Illuminate\Http\Response
     */
    public function authenticate(AccountManager $manager)
    {
        // authenticate the user
        try {
            $data = input();
            $user = $manager->authenticate($data);
        }

        // validation failed
        catch (ValidationException $e) {
            return response([
                'status' => 'validation_failed',
                'message' => $e->getMessage(),
            ], 400);
        }

        // authentication failed
        catch (AuthException $e) {
            return response([
                'status' => 'authentication_failed',
                'message' => $e->getMessage(),
            ], 403);
        }

        return $user;
    }

    /**
     * Register a user.
     * 
     * @return \Illuminate\Http\Response
     */
    public function register(AccountManager $manager)
    {
        // attempt to create the account
        try {
            $data = input();
            $user = $manager->create($data);
        }

        // registration is disabled
        catch (RegistrationDisabledException $e) {
            return response([
                'status' => 'disabled',
                'message' => trans('rainlab.user::lang.account.registration_disabled'),
            ], 403);
        }

        // registration is not valid
        catch (ValidationException $e) {
            return response([
                'status' => 'validation_failed',
                'message' => $e->getMessage(),
            ], 400);
        }

        return $user;
    }

    /**
     * Reset a user's password.
     * 
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(AccountManager $manager)
    {
        // attempt to reset the user's password
        try {
            $manager->resetPassword(input());
        }

        // invalid
        catch (ValidationException $e) {
            return response([
                'status' => 'validation_failed',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response([
            'status' => 'success',
        ], 200);
    }

    /**
     * Send the user a password reset link.
     * 
     * @return \Illuminate\Http\Response
     */
    public function sendResetEmail(AccountManager $manager)
    {
        try {
            $data = input();

            $manager->sendResetEmail($data['email']);
        }

        // invalid user
        catch (InvalidUserException $e) {
            return response([
                'status' => 'invalid_user',
                'message' => trans('rainlab.user::lang.account.invalid_user'),
            ], 500);
        }

        return response([
            'status' => 'success',
        ]);
    }

    /**
     * Get the currently authenticated user.
     * 
     * @return Response
     */
    public function user(AccountManager $manager)
    {
        return $manager->getAuthenticatedUser();
    }
}