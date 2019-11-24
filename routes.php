<?php

if (config('bedard.rainlabuserapi::apiEnable')) {
    $activateRoute = config('bedard.rainlabuserapi::activateRoute');
    $apiPrefix = config('bedard.rainlabuserapi::apiPrefix');

    // api
    Route::prefix($apiPrefix)->middleware('web')->group(function () {
        // auth
        Route::prefix('auth')->group(function () {
            Route::post('login', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@login');
            Route::any('logout', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@logout');
            Route::any('stop-impersonation', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@stopImpersonation');
        });

        // users
        Route::prefix('users')->group(function () {
            Route::post('', 'Bedard\RainLabUserApi\Http\Controllers\UsersController@store');
            Route::get('activate/{code}', 'Bedard\RainLabUserApi\Http\Controllers\UsersController@activate');
            Route::post('forgot-password', 'Bedard\RainLabUserApi\Http\Controllers\UsersController@forgotPassword');
            Route::post('reset-password', 'Bedard\RainLabUserApi\Http\Controllers\UsersController@resetPassword');
        });

        // account
        Route::prefix('account')->middleware('RainLab\User\Classes\AuthMiddleware')->group(function () {
            Route::delete('avatar', 'Bedard\RainLabUserApi\Http\Controllers\AccountController@deleteAvatar');
            Route::get('', 'Bedard\RainLabUserApi\Http\Controllers\AccountController@index');
            Route::post('', 'Bedard\RainLabUserApi\Http\Controllers\AccountController@update');
        });
    });
}
