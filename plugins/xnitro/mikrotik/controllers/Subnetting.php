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

    public function listOverrideColumnValue($record, $columnName, $definition){
        // if($columnName == 'ip' && $definition != 'pool'){
        //     return $record->ip.'/'.$record->size;
        // }elseif($columnName == 'range_ip'){
        //     return $record->meta['first_usable_ip'].' - '.$record->meta['last_usable_ip'];
        // }elseif($columnName == 'used'){
        //     $count = count($record->pool_ip);
        //     return ' '.$count;
        // }elseif($columnName == 'remain'){
        //     $count = count($record->pool_ip);
        //     $remain = $record->meta['usable_ip'] - $count;
        //     return ' '.$remain;
        // }
    }

    public function listExtendColumns($widget){
        // $group_id = isset($this->_var['parent_id'])?$this->_var['parent_id']:(isset($this->params[0])?$this->params[0]:null);
        // if($group_id && count($this->params) == 1){
        //     $widget->recordUrl = 'xnitro/mikrotik/subnetting/pool/'.$group_id.'/:id';

        //     $widget->addColumns([
        //         'range_ip'  => [
        //             'label' => 'Usable Range IP',
        //             'type'  => 'text'
        //         ],
        //         'used'  => [
        //             'label' => 'Used IP',
        //             'type'  => 'text'
        //         ],
        //         'remain'    => [
        //             'label' => 'Remains',
        //             'type'  => 'text'
        //         ]
        //     ]);
        // }else{
        //     $widget->recordUrl = 'xnitro/mikrotik/subnetting/index/:id';
        // }
    }

    public function onRequestGroup(){
        $parent_id = post('parent_id', null);
        // $network_size = post('network_size', null);
        if($parent_id){
            // if($network_size == null || empty($network_size)){
            //     throw new ApplicationException('Network Size Not Found');
            //     return false;
            // }
            $network_size = IpHelper::POOL_SIZE;

            IpHelper::requestNewGroupIp($parent_id, $network_size);
        }else{
            IpHelper::requestNewGroupIp();
        }

        return Redirect::back()->with('message','Operation Successful !');
    }

    public function onRequestFormAssignIP(){
        $pool_id = post('pool_id', null);

        $data = IpHelper::assignIpForm($pool_id);
        $data['pool_id'] = $pool_id;

        return $this->makePartial('form_assign', $data);
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

    public function onAssignIp(){
        $pool_id = post('pool_id', null);
        $number_ip = post('number_ip', null);
        $user = post('user', null);

        IpHelper::assignIp($pool_id, $number_ip, $user);

        return Redirect::back()->with('message','Operation Successful !');
    }
}
