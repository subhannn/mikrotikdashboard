<?php namespace Xnitro\Mikrotik\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Xnitro\Mikrotik\Classes\IPHelper;

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
        'pool'  => 'config_list_pool.yaml'
    ];

    private $listController = null;

    public $group_id = null;

    public $parent_id = null;

    public $pool_id = null;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xnitro.Mikrotik', 'mikrotik', 'subnetting');

        $this->listController = $this->asExtension('ListController');
    }

    public function index($parent_id=null){
        $this->parent_id = $parent_id;
        $this->listController->setConfig('config_list_group.yaml');

        $this->listController->index();
    }

    public function pool($group_id=null, $pool_id=null){
        $this->group_id = $group_id;
        $this->pool_id = $pool_id;

        $this->listController->index();
    }

    public function listExtendQueryBefore($query){
        if($this->parent_id != null){
            $query->where('parent_id', $this->parent_id);
        }elseif($this->pool_id != null){
            $query->where('group_id', $this->pool_id);
        }else{
            $query->where('parent_id', 0);
        }
    }

    public function listOverrideColumnValue($record, $columnName){
        if($columnName == 'ip'){
            return $record->ip.'/'.$record->size;
        }
    }

    public function listExtendColumns($widget){
        if($this->parent_id != null){
            $widget->recordUrl = 'xnitro/mikrotik/subnetting/pool/'.$this->parent_id.'/:id';
        }else{
            $widget->recordUrl = 'xnitro/mikrotik/subnetting/index/:id';
        }
    }

    public function onRequestGroup(){
        $helper = IPHelper::instance();

        $parent_id = post('parent_id', null);
        if($parent_id){
            $helper->requestNewGroupIp($parent_id, 29);
        }else{
            $helper->requestNewGroupIp();
        }        
    }
}
