<?php

namespace Bedard\RainLabUserApi;

use Event;
use RainLab\User\Models\Settings as UserSettings;
use System\Classes\PluginBase;

/**
 * Plugin Information File.
 */
class Plugin extends PluginBase
{
    /**
     * @var array Dependencies
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
            'author'        => 'Scott Bedard',
            'description'   => 'An HTTP API for RainLab.User',
            'icon'          => 'icon-leaf',
            'name'          => 'RainLab.User API',
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
        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->model instanceof UserSettings) {
                return;
            }

            $widget->addTabFields([
                // password reset url
                'password_reset_url' => [
                    'comment'     => 'bedard.rainlabuserapi::lang.settings.password_reset_url_comment',
                    'commentHtml' => true,
                    'label'       => 'bedard.rainlabuserapi::lang.settings.password_reset_url_label',
                    'span'        => 'left',
                    'tab'         => 'rainlab.user::lang.settings.signin_tab',
                ],

                // activation url
                'activate_redirect' => [
                    'comment' => 'bedard.rainlabuserapi::lang.settings.activate_redirect_comment',
                    'label'   => 'bedard.rainlabuserapi::lang.settings.activate_redirect_label',
                    'span'    => 'left',
                    'tab'     => 'rainlab.user::lang.settings.activation_tab',
                    'trigger' => [
                        'action'    => 'enable',
                        'condition' => 'value['.UserSettings::ACTIVATE_USER.']',
                        'field'     => 'activate_mode',
                    ],
                ],

                // safe password updates
                'safe_password_updates' => [
                    'comment' => 'bedard.rainlabuserapi::lang.settings.safe_password_updates_comment',
                    'label'   => 'bedard.rainlabuserapi::lang.settings.safe_password_updates_label',
                    'span'    => 'left',
                    'tab'     => 'bedard.rainlabuserapi::lang.settings.api_tab',
                    'type'    => 'switch',
                ],
            ]);
        });
    }
}
