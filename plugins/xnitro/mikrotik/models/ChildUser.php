<?php namespace Xnitro\Mikrotik\Models;

use Model;
use DB;

/**
 * Model
 */
class ChildUser extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = ['pool_ip_id', 'username', 'password', 'last_logout', 'active'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'xnitro_mikrotik_child_user';

    public $belongsTo = [
        'pool_ip'   => [
            'Xnitro\Mikrotik\Models\PoolIp'
        ]
    ];

    public function scopeAllChildUser($query){
        return $query->select(['id', 'username', 'password', 'last_logout', 'active as status', 'created_at'])->get();
    }
}
