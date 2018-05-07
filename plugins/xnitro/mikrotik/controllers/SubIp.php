<?php namespace Xnitro\Mikrotik\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Sub Ip Back-end Controller
 */
class SubIp extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    private $group_id = null;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xnitro.Mikrotik', 'mikrotik', 'subip');
    }

    public function index(){
        return redirect('backend/xnitro/mikrotik/subnetting');
    }

    public function show($id=null){
        $this->group_id = $id;
        $this->asExtension('ListController')->index();
    }

    public function listExtendQueryBefore($query){
        if($this->group_id != null){
            $query->where('group_id', $this->group_id);
        }else{
            $query->where('group_id', 0);
        }
    }
}
