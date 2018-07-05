<?php namespace Xnitro\Mikrotik\Facades;

use October\Rain\Support\Facade;

class IpHelper extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor() { return 'xnitro.ip'; }
}
