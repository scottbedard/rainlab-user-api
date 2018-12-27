<?php

Route::prefix('api/vuetober/rainlab/user')
    ->middleware('web')->group(function() {

        //
        // public routes
        //
        Route::any('signout', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@signout');
        Route::any('stop-impersonating', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@stopImpersonating');
        Route::get('activate', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@activate');
        Route::get('reset-password', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@getUserByResetCode');
        Route::post('register', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@register');
        Route::post('reset-password', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@resetPassword');
        Route::post('send-reset-email', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@sendResetEmail');
        Route::post('signin', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@authenticate');

        //
        // authenticated routes
        //
        Route::group(['middleware' => 'RainLab\User\Classes\AuthMiddleware'], function () {
            Route::get('user', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@user');
            Route::post('user', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@update');
            Route::delete('user/avatar', 'Vuetober\RainLabUserApi\Http\Controllers\AuthController@deleteAvatar');
        });
    });