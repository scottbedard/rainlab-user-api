<?php

namespace Vuetober\RainLabUserApi;

use Backend;
use Event;
use System\Classes\PluginBase;
use RainLab\User\Models\Settings as UserSettings;

/**
 * Auth Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * @var array   Dependencies
     */
    public $require = [
        'RainLab.User',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'author' => 'Vuetober',
            'description' => 'An HTTP API for RainLab.User',
            'icon' => 'icon-leaf',
            'name' => 'RainLab.User API',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        // extend the rainlab.user settings model
        Event::listen('backend.form.extendFields', function($widget) {
            if (!$widget->model instanceof UserSettings) {
                return;
            }

            $widget->addTabFields([
                // password reset url
                'password_reset_url' => [
                    'comment'   => 'givingteam.auth::lang.settings.password_reset_url_comment',
                    'label'     => 'givingteam.auth::lang.settings.password_reset_url_label',
                    'span'      => 'left',
                    'tab'       => 'API',
                ],

                // activation redirect
                'activation_redirect' => [
                    'comment'   => 'givingteam.auth::lang.settings.activation_redirect_comment',
                    'label'     => 'givingteam.auth::lang.settings.activation_redirect_label',
                    'span'      => 'left',
                    'tab'       => 'API',
                ],
            ]);
        });
    }
}
