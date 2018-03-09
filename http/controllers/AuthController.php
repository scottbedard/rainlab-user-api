<?php namespace GivingTeam\Auth\Http\Controllers;

use ApplicationException;
use Exception;
use GivingTeam\Auth\Classes\AccountManager;
use GivingTeam\Auth\Exceptions\RegistrationDisabledException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ValidationException;

class AuthController extends Controller
{

    /**
     * Register a user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, AccountManager $account)
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

        catch (Exception $e) {
            return response([
                'status' => 'unknown_error',
            ], 500);
        }
        
        // @todo: fire an event

        return $user;
    }
}