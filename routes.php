<?php

Route::prefix('api/givingteam/auth')->middleware('web')->group(function() {

    // public routes
    Route::any('signout', 'GivingTeam\Auth\Http\Controllers\AuthController@signout');
    Route::any('stop-impersonating', 'GivingTeam\Auth\Http\Controllers\AuthController@stopImpersonating');
    Route::get('activate', 'GivingTeam\Auth\Http\Controllers\AuthController@activate');
    Route::post('register', 'GivingTeam\Auth\Http\Controllers\AuthController@register');
    Route::post('reset-password', 'GivingTeam\Auth\Http\Controllers\AuthController@resetPassword');
    Route::post('send-reset-email', 'GivingTeam\Auth\Http\Controllers\AuthController@sendResetEmail');
    Route::post('signin', 'GivingTeam\Auth\Http\Controllers\AuthController@authenticate');

    // authenticated routes
    Route::group(['middleware' => 'RainLab\User\Classes\AuthMiddleware'], function () {
        Route::get('user', 'GivingTeam\Auth\Http\Controllers\AuthController@user');
        Route::post('user', 'GivingTeam\Auth\Http\Controllers\AuthController@update');
    });
});