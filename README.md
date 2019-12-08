# rainlab-user-api

[![Build status](https://img.shields.io/circleci/build/github/scottbedard/rainlab-user-api)](https://circleci.com/gh/scottbedard/rainlab-user-api)
[![Code coverage](https://img.shields.io/codecov/c/github/scottbedard/rainlab-user-api)](https://codecov.io/gh/scottbedard/rainlab-user-api)
[![Code style](https://github.styleci.io/repos/124563300/shield?style=flat)](https://github.styleci.io/repos/124563300)
[![MIT License](https://img.shields.io/github/license/scottbedard/rainlab-user-api?color=blue)](https://github.com/scottbedard/rainlab-user-api/blob/master/LICENSE)

A simple and extendable HTTP API for [RainLab.User](https://github.com/rainlab/user-plugin).

- [Installation & configuration](#installation--configuration)
- [Basic Usage](#basic-usage)
- [Adding middleware](#adding-middleware)
- [Endpoints](#endpoints)

## Installation & configuration

To install the API, run the following commands from your root October directory:

```bash
git clone git@github.com:scottbedard/rainlab-user-api.git plugins/bedard/rainlabuserapi
```

By default, all routes are grouped behind a `/api/rainlab/user` prefix. To override this, add the following to a `.env` file at the root of your October installation. Alternatively, you can use October's [file based configuration](https://octobercms.com/docs/plugin/settings#file-configuration).

```
RAINLAB_USER_API_PREFIX="/your/custom/prefix"
```

To disable the API completely, add the following environment variable:

```
RAINLAB_USER_API_ENABLE=false
```

## Basic usage

To get the authenticated user, use the `AccountManager` class.

```php
use Bedard\RainLabUserApi\Classes\AccountManager;

$user = AccountManager::getAuthenticatedUser();
```

Using this method to fetch the `User` model will trigger a `bedard.rainlabuserapi.afterGetUser` event. This can be useful when other data is needed with the user. As an example, here we'll configure the API to load the user's avatar.

```php
public function boot()
{
    Event::listen('bedard.rainlabuserapi.afterGetUser', function ($user) {
        $user->load(['avatar']);
    });
}
```

## Adding Middleware

All endpoints use a base controller that can be extended. This can be used to add middleware that make the responses consistent with the rest of your API. To do this, add the following to your `Plugin.php` file. See the [October documentation](https://octobercms.com/docs/plugin/registration#registering-middleware) for more information on using middleware.

```php
public function boot()
{
    \Bedard\RainLabUserApi\Classes\ApiController::extend(function($controller) {
        $controller->middleware('Path\To\Custom\Middleware');
    });
}
```

## Endpoints

##### `POST: /api/rainlab/user/auth/login`

Authenticate a user.

##### `ANY: /api/rainlab/user/auth/logout`

Log out the authenticated user.

##### `ANY: /api/rainlab/user/auth/stop-impersonation`

Stop impersonating a user.

##### `POST: /api/rainlab/user/users`

Create user and trigger activation process.

##### `GET: /api/rainlab/user/users/activate/{code}`

Activate a new user.

##### `POST: /api/rainlab/user/users/forgot-password`

Send the user a link to reset their password.

##### `POST: /api/rainlab/user/users/reset-password`

Reset a user's password.

##### `GET: /api/rainlab/user/account`

Get information about the authenticated user. This route uses the [`AuthMiddleware`](https://github.com/rainlab/user-plugin/blob/master/classes/AuthMiddleware.php), authentication is required to access it.

##### `POST: /api/rainlab/user/account`

Updates information about the authenticated user. This route uses the [`AuthMiddleware`](https://github.com/rainlab/user-plugin/blob/master/classes/AuthMiddleware.php), authentication is required to access it. When changing passwords with safe password updates enabled, the user's current password must be included in the request as `password_current`.

##### `DELETE: /api/rainlab/user/account/avatar`

Deletes the authenticated user's avatar. This route uses the [`AuthMiddleware`](https://github.com/rainlab/user-plugin/blob/master/classes/AuthMiddleware.php), authentication is required to access it.

## License

[MIT](https://github.com/scottbedard/rainlab-user-api/blob/master/LICENSE)

Copyright (c) 2018-present, Scott Bedard.
