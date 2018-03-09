<?php

Route::prefix('api/givingteam/auth')->group(function() {
    Route::resource('/', 'GivingTeam\Auth\Http\Controllers\AuthController');
    // reset password
    // register
    // authenticate
    // un-authenticate
});