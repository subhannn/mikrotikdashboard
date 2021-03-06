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
use Flash;

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
        // $util = new RouterOS\Util(new RouterOS\Client(
        //         '103.97.111.250', 
        //         'tunnelid', 
        //         'tunnelid'
        //     ));
        // $util->setMenu('/ppp secret');
        // $name = 'subhannn_5Hk';
        // $id = $util->find(function($response)use($name){
        //     return preg_match('/^'.$name.'$/', $response->getProperty('name'));
        // });
        // $res = $util->set($id, [
        //     'password'  => 'bebek'
        // ]);
        // echo '<pre>';
        // print_r($res);
        // exit();
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
        // $network_size = post('network_size', null);
        // $user = post('user', null);
        // $server = post('server', null);
        // $date = post('expired_date', null);

        IpHelper::requestNewPoolIp(post());

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

    public function onDeleteTunnel(){
        $checked = post('checked');

        IpHelper::removePoolIp($checked);

        Flash::success('Deleted selected records.');
        return $this->listRefresh();
    }

    public function onActionTunnelIp(){
        $messageFlash = '';
        if (($bulkAction = post('action')) &&
            ($checkedIds = post('checked')) &&
            is_array($checkedIds) &&
            count($checkedIds)
        ) {
            foreach ($checkedIds as $id) {
                switch ($bulkAction) {
                    case 'enabled':
                    case 'disabled':
                        $data = [
                            'id'    => $id,
                            'type'  => 'root'
                        ];
                        IpHelper::actionUserTunnelChild($bulkAction, $data, true, true);
                        $messageFlash = ucfirst($bulkAction).' Action Successful.';
                        break;
                    default:
                        continue;
                        break;
                }
            }
        }

        Flash::success($messageFlash);
        return $this->listRefresh();
    }

    public function onSearchUser(){
        $s = post('term', null);
        $page = post('page', 1);
        if($s){
            $result = User::searchWhere($s, ['name', 'surname'])->paginate(10, $page);
            if($result->total() > 0){
                $data = [];
                $data['pagination']['more'] = false;
                if($result->hasMorePages()){
                    $data['pagination']['more'] = true;
                }
                foreach ($result->items() as $row) {
                    $data['results'][] = [
                        'id'    => (string) $row->id,
                        'text'  => (string) implode(' ', [$row->name, $row->surname])
                    ];
                }

                return $data;
            }
        }

        return [
            'results'  => [
                'results'   => []
            ]
        ];
    }
}
