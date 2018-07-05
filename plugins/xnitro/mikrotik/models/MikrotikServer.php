<?php namespace Xnitro\Mikrotik\Models;

use Model;

/**
 * Model
 */
class MikrotikServer extends Model
{
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = ['name', 'host', 'user', 'pass', 'port', 'default_server'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'xnitro_mikrotik_server';
}
