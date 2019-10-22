# rainlab-user-api

[![Build Status](https://travis-ci.org/scottbedard/rainlab-user-api.svg?branch=master)](https://travis-ci.org/scottbedard/rainlab-user-api)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/scottbedard/rainlab-user-api/blob/master/LICENSE)

A simple HTTP API for [RainLab.User](https://github.com/rainlab/user-plugin).

> **Warning:** This plugin is in active development. Be careful using it, API changes may happen at any time.

### Plugin Usage

To get the authenticated user, use the `AccountManager` class.

```php
use Bedard\RainLabUserApi\Classes\AccountManager;

$user = (new AccountManager)->getAuthenticatedUser();
```

Using this method to fetch the `User` model will trigger a `bedard.rainlabuserapi.afterGetUser` event. This can be useful useful when other data needs to be loaded with the user. In this example, we'll have a plugin load the user's avatar.

```php
class Plugin extends PluginBase
{
    public function boot()
    {
        Event::listen('bedard.rainlabuserapi.afterGetUser', function ($user) {
            $user->load(['avatar']);
        });
    }
}
```

### Endpoints

`/api/rainlab/user`

`/api/rainlab/user/avatar`

`/api/rainlab/user/signout`

`/api/rainlab/user/stop-impersonating`

`/api/rainlab/user/activate`

`/api/rainlab/user/reset-password`

`/api/rainlab/user/register`

`/api/rainlab/user/reset-password`

`/api/rainlab/user/send-reset-email`

`/api/rainlab/user/signin`

### License

[MIT](https://github.com/scottbedard/rainlab-user-api/blob/master/LICENSE)

Copyright (c) 2018-present, Scott Bedard.

