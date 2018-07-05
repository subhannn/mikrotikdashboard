<?php namespace Xnitro\Mikrotik\Models;

use Model;

/**
 * PoolIp Model
 */
class PoolIp extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'xnitro_mikrotik_pool_ip';

    /**
     * @var array Guarded fields
     */
    // protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['ip', 'usable_first_ip', 'usable_last_ip', 'size', 'user_id', 'status', 'server_id', 'username', 'password'];

    /**
     * @var array Relations
     */
    public $hasOne = [
        'user' => [
            'RainLab\User\Models\User',
            'key'   => 'id',
            'otherKey'=> 'user_id'
        ],
        'server'=> [
            'Xnitro\Mikrotik\Models\MikrotikServer',
            'key'   => 'id',
            'otherKey'=> 'server_id'
        ],
    ];
    public $hasMany = [
        'child_user'=> [
            'Xnitro\Mikrotik\Models\ChildUser',
            'key'   => 'pool_ip_id'
        ],
    ];
    public $belongsTo = [];
    public $belongsToMany = [
        
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeCheckUser($query, $server_id, $size, $user_id){
        return $query->where('server_id', $server_id)
            ->where('status', '1')
            ->where('user_id', $user_id);
    }

    public function scopeGetLastIp($query, $server_id, $size){
        return $query->where('server_id', $server_id)
            ->where('size', $size)
            ->where('status', '1')
            ->orderBy('id', 'desc');
    }

    public function getReleaseIp($query, $server_id, $size){
        return $query->where('server_id', $server_id)
            ->where('size', $size)
            ->where('status', '2')
            ->orderBy('id', 'desc');
    }
}
