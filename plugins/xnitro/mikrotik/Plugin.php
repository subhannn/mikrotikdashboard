<?php namespace Xnitro\Mikrotik;

use App;
use Backend;
use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;

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
        $alias = AliasLoader::getInstance();
        $alias->alias('IpHelper', 'Xnitro\Mikrotik\Facades\IpHelper');

        App::singleton('xnitro.ip', function() {
            return \Xnitro\Mikrotik\Classes\IPHelper::instance();
        });
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
                'sideMenu' => [
                    'pool_ip' => [
                        'label' => 'Pool IP',
                        'icon' => 'icon-bars',
                        'url' => Backend::url('xnitro/mikrotik/subnetting/index'),
                        'permissions' => ['xnitro.mikrotik.*'],
                    ],
                    'settings' => [
                        'label' => 'Settings Mikrotik Server',
                        'icon' => 'icon-desktop',
                        'url' => Backend::url('xnitro/mikrotik/settings'),
                        'permissions' => ['xnitro.mikrotik.admin'],
                    ],
                ]
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'config' => [
                'label'       => 'Mikrotik',
                'icon'        => 'icon-leaf',
                'description' => 'Setting for Mikrotik Plugin.',
                'class'       => 'Xnitro\Mikrotik\Models\Settings',
                // 'permissions' => ['rainlab.builder.manage_plugins'],
                'order'       => 600
            ]
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'themes_path'   => 'themes_path',
                'user_permissions'=> function($str=''){
                    return json_encode(\Xnitro\Mikrotik\Classes\IPHelper::instance()->userPermissions());
                }
            ]
        ];
    }
}
