<?php namespace Xnitro\Mikrotik\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Xnitro\Mikrotik\Classes\IPHelper;
use ApplicationException;
use Redirect;
use RainLab\User\Models\User;

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
        'index' => 'config_list_group.yaml',
        'group' => 'config_list_group.yaml',
        'pool'  => 'config_list_pool.yaml'
    ];

    private $listController = null;
    private $formController = null;

    public $definition = 'index';

    public $_var = [];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xnitro.Mikrotik', 'subnetting');

        $this->listController = $this->asExtension('ListController');
        // $this->formController = $this->asExtension('FormController');
    }

    public function index($parent_id=null){
        $this->definition = $parent_id==null?'index':'group';
        $this->_var['parent_id'] = $parent_id;
        $this->listController->setConfig('config_list_group.yaml');

        $this->listController->index();
    }

    public function pool($group_id=null, $pool_id=null){
        $this->_var['group_id'] = $group_id;
        $this->_var['pool_id'] = $pool_id;

        $helper = IPHelper::instance();

        $data = $helper->assignIpForm($pool_id, true);
        $this->_var['available_ip'] = $data;

        $this->listController->index();
    }

    public function assign($group_id=null, $pool_id=null){
        $this->_var['group_id'] = $group_id;
        $this->_var['pool_id'] = $pool_id;
    }

    public function listExtendQueryBefore($query, $definition){
        if($definition == 'group'){
            $id = isset($this->_var['parent_id'])?$this->_var['parent_id']:(isset($this->params[0])?$this->params[0]:null);
            if($id)
                $query->where('parent_id', $id);
        }elseif($definition == 'pool'){
            $id = isset($this->_var['pool_id'])?$this->_var['pool_id']:(isset($this->params[1])?$this->params[1]:null);
            if($id)
                $query->where('group_id', $id);
        }elseif($definition == 'index'){
            $query->where('parent_id', 0);
        }
    }

    public function listOverrideColumnValue($record, $columnName, $definition){
        if($columnName == 'ip' && $definition != 'pool'){
            return $record->ip.'/'.$record->size;
        }elseif($columnName == 'range_ip'){
            return $record->meta['first_usable_ip'].' - '.$record->meta['last_usable_ip'];
        }elseif($columnName == 'used'){
            $count = count($record->pool_ip);
            return ' '.$count;
        }elseif($columnName == 'remain'){
            $count = count($record->pool_ip);
            $remain = $record->meta['usable_ip'] - $count;
            return ' '.$remain;
        }
    }

    public function listExtendColumns($widget){
        $group_id = isset($this->_var['parent_id'])?$this->_var['parent_id']:(isset($this->params[0])?$this->params[0]:null);
        if($group_id && count($this->params) == 1){
            $widget->recordUrl = 'xnitro/mikrotik/subnetting/pool/'.$group_id.'/:id';

            $widget->addColumns([
                'range_ip'  => [
                    'label' => 'Usable Range IP',
                    'type'  => 'text'
                ],
                'used'  => [
                    'label' => 'Used IP',
                    'type'  => 'text'
                ],
                'remain'    => [
                    'label' => 'Remains',
                    'type'  => 'text'
                ]
            ]);
        }else{
            $widget->recordUrl = 'xnitro/mikrotik/subnetting/index/:id';
        }
    }

    public function onRequestGroup(){
        $helper = IPHelper::instance();

        $parent_id = post('parent_id', null);
        // $network_size = post('network_size', null);
        if($parent_id){
            // if($network_size == null || empty($network_size)){
            //     throw new ApplicationException('Network Size Not Found');
            //     return false;
            // }
            $network_size = IPHelper::POOL_SIZE;

            $helper->requestNewGroupIp($parent_id, $network_size);
        }else{
            $helper->requestNewGroupIp();
        }

        return Redirect::back()->with('message','Operation Successful !');
    }

    public function onRequestFormAssignIP(){
        $pool_id = post('pool_id', null);
        $helper = IPHelper::instance();

        $data = $helper->assignIpForm($pool_id);
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
        $helper = IPHelper::instance();

        $helper->assignIp($pool_id, $number_ip, $user);

        return Redirect::back()->with('message','Operation Successful !');
    }
}
