<?php namespace GivingTeam\Auth\Http\Controllers;

use Auth;
use ApplicationException;
use Exception;
use GivingTeam\Auth\Classes\AccountManager;
use GivingTeam\Auth\Exceptions\RegistrationDisabledException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
     * Get the currently authenticated user.
     * 
     * @return Response
     */
    public function user(AccountManager $manager)
    {
        return $manager->getAuthenticatedUser();
    }
}