<?php namespace Xnitro\Mikrotik\Models;

use Lang;
use Model;
use Xnitro\Mikrotik\Models\MikrotikServer;

class Settings extends Model
{
    /**
     * @var array Behaviors implemented by this model.
     */
    public $implement = [
        \System\Behaviors\SettingsModel::class
    ];

    public $settingsCode = 'mikrotik_settings';
    public $settingsFields = 'fields.yaml';

    protected $listServer = null;

    public function initSettingsData()
    {
        // print_r($this->attributes);
        // exit();
        // $this->mikrotik_host = '103.97.111.250';
        // $this->mikrotik_user = 'tunnelid';
        // $this->mikrotik_pass = 'tunnelid';
        // $this->mikrotik_port = 8728;
    }

    public function getMikrotikServer(){
        if($this->listServer == null){
            return $this->listServer = MikrotikServer::all()->toArray();
        }

        return $this->listServer;
    }

    public function afterFetch(){
        if(!$this->server){
            $server = MikrotikServer::all()->toArray();
            if($server){
                $this->server = $server;
            }
        }
    }

    public function beforeSave(){
        // 103.97.111.250
        $server = $this->get('server');
        if($server){
            $exists = [];
            foreach ($server as $sv) {
                if(isset($sv['id']) && empty($sv['id'])){
                    $data = array_only($sv, ['host', 'user']);
                }else{
                    $data = array_only($sv, ['id']);
                }
                $mikrotik = MikrotikServer::firstOrNew($data);
                $mikrotik->fill($sv);
                $mikrotik->save();
                $exists[] = $mikrotik->id;
            }
            if($exists){
                MikrotikServer::whereNotIn('id', $exists)->delete();
            }
        }
    }
}
