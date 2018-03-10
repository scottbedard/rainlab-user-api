<?php

Route::prefix('api/givingteam/auth')->group(function() {

    // activate a user
    Route::get('activate', 'GivingTeam\Auth\Http\Controllers\AuthController@activate');

    // register a user
    Route::post('register', 'GivingTeam\Auth\Http\Controllers\AuthController@register');

    // authentication required
    Route::group(['middleware' => 'RainLab\User\Classes\AuthMiddleware'], function () {
        Route::get('user', 'GivingTeam\Auth\Http\Controllers\AuthController@user');
    });
});