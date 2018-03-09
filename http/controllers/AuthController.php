<?php namespace GivingTeam\Auth\Http\Controllers;

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
     * Register the user.
     * 
     * @return \Illuminate\Http\Response
     */
    public function store(AccountManager $account)
    {
        // attempt to create the account
        try {
            $data = input();
            $user = $account->create($data);
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
}