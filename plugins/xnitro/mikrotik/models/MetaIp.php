<?php namespace Xnitro\Mikrotik\Models;

use Model;

/**
 * MetaIp Model
 */
class MetaIp extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'xnitro_mikrotik_meta_ip';

    public $timestamps = false;

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'group' => [
            'Xnitro\Mikrotik\Models\GroupIp', 
            'key'   => 'ip_id',
        ]
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeCheckMetaKeyExists($query, $key=''){
        $query->where('key', $key);

        $first = $query->first();
        return isset($first->key) ? $first : [];
    }

    public function scopeSync($query, $keys=[]){
        if($keys){
            $query->whereNotIn('key', $keys)->delete();
        }
    }
}
