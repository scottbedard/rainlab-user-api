<?php

use Faker\Generator;
use RainLab\User\Models\User;

/*
 * @var $factory Illuminate\Database\Eloquent\Factory
 */
$factory->define(User::class, function (Generator $faker) {
    return [
        'email'                 => $faker->email,
        'name'                  => $faker->firstName,
        'password'              => '12345678',
        'password_confirmation' => '12345678',
        'surname'               => $faker->lastName,
        'username'              => $faker->username,
    ];
});
