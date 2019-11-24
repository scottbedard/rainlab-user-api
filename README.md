# rainlab-user-api

[![Build Status](https://img.shields.io/circleci/build/github/scottbedard/rainlab-user-api)](https://circleci.com/gh/scottbedard/rainlab-user-api)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/scottbedard/rainlab-user-api/blob/master/LICENSE)

A simple and extendable HTTP API for [RainLab.User](https://github.com/rainlab/user-plugin).

> **Warning:** This plugin is in active development. Be careful using it, API changes may happen at any time.

- [Basic Usage](#basic-usage)
- [Middleware](#middleware)
- [Endpoints](#endpoints)

## Basic usage

To get the authenticated user, use the `AccountManager` class.

```php
use Bedard\RainLabUserApi\Classes\AccountManager;

$user = (new AccountManager)->getAuthenticatedUser();
```

Using this method to fetch the `User` model will trigger a `bedard.rainlabuserapi.afterGetUser` event. This can be useful useful when other data needs to be loaded with the user. In this example, we'll have a plugin load the user's avatar.

```php
public function boot()
{
    Event::listen('bedard.rainlabuserapi.afterGetUser', function ($user) {
        $user->load(['avatar']);
    });
}
```

## Configuration

By default, all routes are grouped behind a `/api/rainlab/user` prefix. To override this behavior, add the following to a `.env` file at the root of your October installation.

```
RAINLAB_USER_API_PREFIX="/your/custom/prefix"
```

Additionally, the API can be disabled completely by adding the following:

```
RAINLAB_USER_API_ENABLE=false
```

## Middleware

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

##### `POST: /api/rainlab/user/users`

Registers a user, and triggers related events and activation processes.

##### `GET: /api/rainlab/users/user/activate/{code}`

Activate a new user.

## 0.x Endpoints

> **Note:** We're migrating this plugin to a stable release. These endpoints are deprecated, and will soon be removed.

###### `GET: /api/rainlab/user`

Get information about the authenticated user. This route uses the [`AuthMiddleware`](https://github.com/rainlab/user-plugin/blob/master/classes/AuthMiddleware.php), authentication is required to access it.

###### `POST: /api/rainlab/user`

Updates information about the authenticated user. This route uses the [`AuthMiddleware`](https://github.com/rainlab/user-plugin/blob/master/classes/AuthMiddleware.php), authentication is required to access it.

###### `DELETE: /api/rainlab/user/avatar`

Deletes the authenticated user's avatar. This route uses the [`AuthMiddleware`](https://github.com/rainlab/user-plugin/blob/master/classes/AuthMiddleware.php), authentication is required to access it.

###### `ANY: /api/rainlab/user/signout`

Sign out the authenticated user.

###### `ANY: /api/rainlab/user/stop-impersonating`

Stop impersonating a user

###### `GET: /api/rainlab/user/reset-password`

Get user information by password reset code.

###### `POST: /api/rainlab/user/reset-password`

Reset a user's password.

###### `POST: /api/rainlab/user/send-reset-email`

Initiate the password reset process for a user.

###### `POST: /api/rainlab/user/signin`

Authenticate a user.

## License

[MIT](https://github.com/scottbedard/rainlab-user-api/blob/master/LICENSE)

Copyright (c) 2018-present, Scott Bedard.
