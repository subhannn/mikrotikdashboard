<?php namespace Xnitro\Mikrotik\Components;

use Cms\Classes\ComponentBase;
use Auth;
use ApplicationException;
use IpHelper;

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
        // $this->addJs('assets/dashboard/build/dist/bundle.js');
    }

    public function onCheckUser(){
        return IpHelper::getActiveUserData();
    }

    public function onGetData(){
        $type = post('type', null);

        switch ($type) {
            case 'tunnel_ip':
                return IpHelper::getTunnelIpList();
                break;
            default:
                break;
        }

        throw new ApplicationException('Invalid Request.');
    }

    public function onPostData(){
        $type = post('type', null);
        $data = post('data', []);

        switch ($type) {
            case 'new_tunnel_user':
                return IpHelper::createUserTunnelChild();
                break;
            case 'remove_child_user':
                return IpHelper::actionUserTunnelChild('delete', $data);
                break;
            case 'enabled_child_user':
                return IpHelper::actionUserTunnelChild('enabled', $data);
                break;
            case 'disabled_child_user':
                return IpHelper::actionUserTunnelChild('disabled', $data);
                break;
            case 'change_tunnel_password':
                return IpHelper::actionUserTunnelChild('change_password', $data);
            default:
                break;
        }
    }
}
