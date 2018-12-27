<?php namespace GivingTeam\Auth\Http\Controllers;

use ApplicationException;
use Auth;
use Exception;
use GivingTeam\Auth\Classes\AccountManager;
use GivingTeam\Auth\Classes\ApiController;
use GivingTeam\Auth\Exceptions\EmailTakenException;
use GivingTeam\Auth\Exceptions\InvalidUserException;
use GivingTeam\Auth\Exceptions\RegistrationDisabledException;
use Illuminate\Http\Request;
use Input;
use October\Rain\Auth\AuthException;
use October\Rain\Database\ModelException;
use RainLab\User\Models\Settings as UserSettings;
use ValidationException;

class AuthController extends ApiController
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

        // validation failed
        catch (ValidationException $e) {
            return $this->validationError($e);
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
            return $this->validationError($e);
        }

        // authentication failed
        catch (AuthException $e) {
            return response([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 403);
        }

        return $user;
    }

    /**
     * Delete a user's avatar
     * 
     * @return array
     */
    public function deleteAvatar()
    {
        try {
            Auth::getUser()->avatar()->delete();
        } catch(Exception $e) {
            return response([
                'message' => $e->getMessage(),
                'status' => 'failed',
            ], 500);
        }

        return response(['status' => 'success']);
    }

    /**
     * Fetch a user by their password reset code.
     * 
     * @return \Illuminate\Http\Resonse
     */
    public function getUserByResetCode(AccountManager $manager)
    {
        try {
            $user = $manager->getUserByResetCode(input('code'));
        } catch (Exception $e) {
            return response([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
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
        
        // validation failed
        catch (ValidationException $e) {
            return $this->validationError($e);
        }

        // email address is taken
        catch (EmailTakenException $e) {
            return response([
                'status' => 'email_taken',
            ], 500);
        }

        // unknown error
        catch (Exception $e) {
            return response([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
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

        // validation failed
        catch (ValidationException $e) {
            return $this->validationError($e);
        }

        return response([
            'status' => 'success',
        ], 200);
    }

    /**
     * Sign a user out.
     * 
     * @return Illuminate\Http\Response
     */
    public function signout(AccountManager $manager)
    {
        $manager->signout();

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
     * Stop impersonating a user
     * 
     * @return Illuminate\Http\Response
     */
    public function stopImpersonating(AccountManager $manager)
    {
        // sign out if we're not impersonating anyone
        try {
            $manager->stopImpersonating();
        }

        // unknown error
        catch (Exception $e) {
            return response([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }

        return response([
            'status' => 'success',
        ], 200);
    }

    /**
     * Update the user's account.
     * 
     * @return Response
     */
    public function update(AccountManager $manager)
    {
        // attempt to update the user
        try {
            $data = input();

            if (Input::hasFile('avatar')) {
                $data['avatar'] = Input::file('avatar');
            }

            $user = $manager->update($data);
        }

        // authentication error
        catch (AuthException $e) {
            return response([
                'status' => 'unauthorized',
            ], 403);
        }

        // email address is taken
        catch (EmailTakenException $e) {
            return response([
                'status' => 'email_taken',
            ], 500);
        }

        // validation failed
        catch (ValidationException $e) {
            return $this->validationError($e);
        }

        // unknown error
        catch (Exception $e) {
            return response([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }

        return $user;
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