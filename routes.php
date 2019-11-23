<?php

if (config('bedard.rainlabuserapi::apiEnable')) {
    $activateRoute = config('bedard.rainlabuserapi::activateRoute');
    $apiPrefix = config('bedard.rainlabuserapi::apiPrefix');

    // api
    Route::prefix($apiPrefix)->middleware('web')->group(function () {

        // users
        Route::prefix('users')->group(function () {
            Route::post('/', 'Bedard\RainLabUserApi\Http\Controllers\UsersController@store');
            Route::get('/activate/{code}', 'Bedard\RainLabUserApi\Http\Controllers\UsersController@activate');
        });
    });
}

// Route::prefix('api/rainlab/user')->middleware('web')->group(function() {

//     // // public routes
//     // Route::any('signout', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@signout');
//     // Route::any('stop-impersonating', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@stopImpersonating');
//     // Route::get('activate', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@activate');
//     // Route::get('reset-password', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@getUserByResetCode');
//     // Route::post('register', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@register');
//     // Route::post('reset-password', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@resetPassword');
//     // Route::post('send-reset-email', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@sendResetEmail');
//     // Route::post('signin', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@authenticate');

//     // // authenticated routes
//     // Route::group(['middleware' => 'RainLab\User\Classes\AuthMiddleware'], function () {
//     //     Route::get('user', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@user');
//     //     Route::post('user', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@update');
//     //     Route::delete('user/avatar', 'Bedard\RainLabUserApi\Http\Controllers\AuthController@deleteAvatar');
//     // });
// });