<?php namespace Xnitro\Mikrotik\Models;

use Model;
use Xnitro\Mikrotik\Models\MetaIp;

/**
 * GroupIp Model
 */
class GroupIp extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'xnitro_mikrotik_group_ip';

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['ip', 'size', 'user_id', 'last_ip', 'parent_id', 'group_name', 'meta'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'metas'  => [
            'Xnitro\Mikrotik\Models\MetaIp',
            'key'   => 'ip_id',
            'delete' => true,
        ],
        'pool_ip'   => [
            'Xnitro\Mikrotik\Models\PoolIp',
            'key'   => 'group_id',
            'delete' => true,
        ]
    ];

    public $belongsTo = [
        'user'  => ['Backend\Models\User']
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    private $meta = [];

    public function setMetaAttribute($meta){
        $this->meta = $meta;
    }

    public function getMetaAttribute($original=false){
        if($original){
            return $this->meta;
        }else{
            return $this->metas()->lists('value', 'key');
        }
    }

    public function afterSave(){
        if(is_array($this->meta) && count($this->meta) > 0){
            $metas = [];
            $this->meta = array_filter($this->meta, 'strlen');
            foreach ($this->meta as $key => $value) {
                if(empty($value)) continue;

                $meta = $this->metas()->checkMetaKeyExists($key);
                if(!$meta instanceof MetaIp){
                    $meta = new MetaIp();
                    $meta->group()->associate($this);
                }
                $meta->key = $key;
                $meta->value = $value;
                $meta->type = 0;
                $meta->save();
            }
            $this->metas()->sync(array_keys($this->meta));
        }

    }

    public function scopeGetLastGroup($query, $parent_id=null){
        if($parent_id != null && !empty($parent_id)){
            $query->where('parent_id', $parent_id);
        }else{
            $query->where('parent_id', '0');
        }

        return $query->orderBy('created_at', 'desc');
    }
}
