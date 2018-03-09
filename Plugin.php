<?php namespace GivingTeam\Auth;

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
            'name'        => 'Auth',
            'description' => 'No description provided yet...',
            'author'      => 'GivingTeam',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

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
                'activation_redirect' => [
                    'comment'   => 'givingteam.auth::lang.settings.activation_redirect_comment',
                    'label'     => 'givingteam.auth::lang.settings.activation_redirect_label',
                    'span'      => 'left',
                    'tab'       => 'rainlab.user::lang.settings.activation_tab',
                ],
            ]);
        });
    }
}
