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
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
}
