<?php namespace Xnitro\Mikrotik\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use IpHelper;
use ApplicationException;
use Redirect;
use RainLab\User\Models\User;
use Xnitro\Mikrotik\Models\Settings as SettingsModel;
use Auth;
use Crypt;
use PEAR2\Net\RouterOS;

/**
 * Subnetting Back-end Controller
 */
class Subnetting extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        // 'Backend.Behaviors.FormController'
    ];

    public $formConfig = 'config_form_assign.yaml';
    public $listConfig = [
        'index' => 'config_list_pool.yaml',
        // 'group' => 'config_list_group.yaml',
        // 'pool'  => 'config_list_pool.yaml'
    ];

    private $listController = null;
    private $formController = null;

    public $definition = 'index';

    public $_var = [];

    public $settings = null;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xnitro.Mikrotik', 'subnetting');

        $this->listController = $this->asExtension('ListController');
        $this->settings = SettingsModel::instance();
        // $this->formController = $this->asExtension('FormController');
        $this->addJs('/plugins/xnitro/mikrotik/assets/js/subnetting.js');

        $this->vars['activeServer'] = input('server', null);
    }

    public function index(){
        BackendMenu::setContext('Xnitro.Mikrotik', 'subnetting', 'pool_ip');

        $this->listController->index();
    }

    public function onRequestPoolIp(){
        $server = $this->settings->getMikrotikServer();
        if($activeServer = $this->vars['activeServer']){
            $server = array_where($server, function ($value, $key)use($activeServer) {
                if($value['id'] == $activeServer){
                    return $value;
                }

                return false;
            });
        }

        $data = [
            'server'    => $server
        ];

        return $this->makePartial('create_pool_form', $data);
    }

    public function onSubmitRequestPoolIp(){
        $network_size = post('network_size', null);
        $user = post('user', null);
        $server = post('server');

        IpHelper::requestNewPoolIp($network_size, $user, $server);

        return Redirect::back()->with('message','Operation Successful !');
    }

    public function pool($group_id=null, $pool_id=null){
        $this->_var['group_id'] = $group_id;
        $this->_var['pool_id'] = $pool_id;

        $data = IpHelper::assignIpForm($pool_id, true);
        $this->_var['available_ip'] = $data;

        $this->listController->index();
    }

    public function assign($group_id=null, $pool_id=null){
        $this->_var['group_id'] = $group_id;
        $this->_var['pool_id'] = $pool_id;
    }

    public function listExtendQueryBefore($query, $definition){
        if($this->vars['activeServer']){
            $query->where('server_id', $this->vars['activeServer']);
        }
    }
}
