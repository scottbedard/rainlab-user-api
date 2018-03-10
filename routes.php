<?php

Route::prefix('api/givingteam/auth')->group(function() {

    // authenticate a user
    Route::post('/', 'GivingTeam\Auth\Http\Controllers\AuthController@authenticate');

    // activate a user
    Route::get('activate', 'GivingTeam\Auth\Http\Controllers\AuthController@activate');

    // register a user
    Route::post('register', 'GivingTeam\Auth\Http\Controllers\AuthController@register');

    // send a password reset email
    Route::post('send-reset-email', 'GivingTeam\Auth\Http\Controllers\AuthController@sendResetEmail');

    // reset password
    Route::post('reset-password', 'GivingTeam\Auth\Http\Controllers\AuthController@resetPassword');

    // authentication required
    Route::group(['middleware' => 'RainLab\User\Classes\AuthMiddleware'], function () {
        Route::get('user', 'GivingTeam\Auth\Http\Controllers\AuthController@user');
    });
});