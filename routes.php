<?php

Route::prefix('api/givingteam/auth')->group(function() {
    Route::resource('/', 'GivingTeam\Auth\Http\Controllers\AuthController');
    Route::get('activate', 'GivingTeam\Auth\Http\Controllers\AuthController@activate');
    
    // reset password
    // register
    // authenticate
    // un-authenticate
});