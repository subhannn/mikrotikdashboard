<?php namespace Xnitro\Mikrotik\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Xnitro\Mikrotik\Classes\IPHelper;
use ApplicationException;
use Redirect;

/**
 * Subnetting Back-end Controller
 */
class Subnetting extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController'
    ];

    // public $formConfig = 'config_form.yaml';
    public $listConfig = [
        'index' => 'config_list_group.yaml',
        'group' => 'config_list_group.yaml',
        'pool'  => 'config_list_pool.yaml'
    ];

    private $listController = null;

    public $group_id = null;

    public $parent_id = null;

    public $pool_id = null;

    public $definition = 'index';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xnitro.Mikrotik', 'subnetting');

        $this->listController = $this->asExtension('ListController');
    }

    public function index($parent_id=null){
        $this->parent_id = $parent_id;
        $this->definition = $parent_id==null?'index':'group';
        $this->listController->setConfig('config_list_group.yaml');

        $this->listController->index();
    }

    public function pool($group_id=null, $pool_id=null){
        $this->group_id = $group_id;
        $this->pool_id = $pool_id;

        $this->listController->index();
    }

    public function listExtendQueryBefore($query, $definition){
        if($definition == 'group'){
            $query->where('parent_id', $this->parent_id);
        }elseif($definition == 'pool'){
            $query->where('group_id', $this->pool_id);
        }else{
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
            $count = $record->pool_ip()->count();
            $remain = $record->meta['usable_ip'] - $count;
            return $remain;
        }
    }

    public function listExtendColumns($widget){
        if($this->parent_id != null){
            $widget->recordUrl = 'xnitro/mikrotik/subnetting/pool/'.$this->parent_id.'/:id';

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
        $network_size = post('network_size', null);
        if($parent_id){
            if($network_size == null || empty($network_size)){
                throw new ApplicationException('Network Size Not Found');
                return false;
            }

            $helper->requestNewGroupIp($parent_id, $network_size);
        }else{
            $helper->requestNewGroupIp();
        }

        return Redirect::back()->with('message','Operation Successful !');
    }

    public function onRequestFormPool(){
        return $this->makePartial('create_pool_form', post());
    }
}
