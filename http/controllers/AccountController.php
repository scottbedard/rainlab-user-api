<?php

namespace Bedard\RainLabUserApi\Http\Controllers;

use Auth;
use Bedard\RainLabUserApi\Classes\ApiController;

class AccountController extends ApiController
{
    public function index()
    {
        return Auth::getUser();
    }
}