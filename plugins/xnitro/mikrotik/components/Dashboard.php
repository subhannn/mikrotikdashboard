<?php namespace Xnitro\Mikrotik\Components;

use Cms\Classes\ComponentBase;

class Dashboard extends ComponentBase
{
    public function defineProperties()
    {
        return [
        ];
    }

    public function componentDetails()
    {
        return [
            'name'        => 'Dashboard Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function onRun(){
        $this->addJs('assets/dashboard/build/dist/bundle.js');
    }
}
