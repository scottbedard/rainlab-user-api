<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Enable API
     |--------------------------------------------------------------------------
     |
     | Set this value to false to disable all API routes.
     |
     */

    'apiEnable' => env('RAINLAB_USER_API_ENABLE', true),

    /*
     |--------------------------------------------------------------------------
     | API prefix
     |--------------------------------------------------------------------------
     |
     | By default, all HTTP routes will be grouped behind the following path.
     | If this conflicts with your app, you can override the route here.
     |
     */

    'apiPrefix' => env('RAINLAB_USER_API_PREFIX', '/api/rainlab/user'),
];
