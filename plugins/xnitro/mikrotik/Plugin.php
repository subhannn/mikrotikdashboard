<?php namespace Xnitro\Mikrotik;

use Backend;
use System\Classes\PluginBase;

/**
 * Mikrotik Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Mikrotik',
            'description' => 'No description provided yet...',
            'author'      => 'Xnitro',
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

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Xnitro\Mikrotik\Components\Dashboard' => 'mikrotikDashboard',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'xnitro.mikrotik.some_permission' => [
                'tab' => 'Mikrotik',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'subnetting' => [
                'label'       => 'Subnetting',
                'url'         => Backend::url('xnitro/mikrotik/subnetting'),
                'icon'        => 'icon-leaf',
                'permissions' => ['xnitro.mikrotik.*'],
                'order'       => 500,
            ],
        ];
    }
}
