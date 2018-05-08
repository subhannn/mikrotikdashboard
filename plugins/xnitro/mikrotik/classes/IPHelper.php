<?php
namespace Xnitro\Mikrotik\Classes;

use Xnitro\Mikrotik\Models\GroupIp;
use IPv4\SubnetCalculator;
use BackendAuth;
use ApplicationException;
use Flash;

class IPHelper{
	use \October\Rain\Support\Traits\Singleton;

	const FIRST_GROUP_IP = '10.0.0.0';

	const GROUP_SIZE = 20;

	public function requestNewGroupIp($parent_id=null, $size=self::GROUP_SIZE){
		$group_name = null;
		if($parent_id != null){
			$parent = GroupIp::find($parent_id);
			if(!isset($parent->id)){
				throw new ApplicationException('Parent ID Not Found');
				return false;
			}

			$group = GroupIp::getLastGroup($parent->id)->first();
			if(!$group){ // create pool group
				$group_ip = $parent->ip;
			}else{
				$curLastIp = $group->last_ip;
				$group_ip = long2ip(ip2long($curLastIp)+1);
			}
			if(ip2long($group_ip) > ip2long($parent->meta['broadcast_address'])){
				throw new ApplicationException('All Pool IP is Sold, Please request new Group IP');
				return false;
			}
			$group_name = BackendAuth::getUser()->login.'_pool_'.uniqid();
			$flash_message = 'Create Pool IP Success';
		}else{
			$group = GroupIp::getLastGroup()->first();
			if(!$group){ // create group
				$group_ip = self::FIRST_GROUP_IP;
			}else{
				$curLastIp = $group->last_ip;
				$group_ip = long2ip(ip2long($curLastIp)+1);
			}
			$subCul = new SubnetCalculator($group_ip, $size);
			$flash_message = 'Create Group IP Success';
			$parent_id = 0;
		}

		$subCul = new SubnetCalculator($group_ip, $size);
		$data = [
			'ip'		=> (string) $group_ip,
			'size'		=> $size,
			'user_id'	=> BackendAuth::getUser()->id,
			'last_ip'	=> $subCul->getBroadcastAddress(),
			'parent_id'	=> $parent_id,
			'meta'		=> [
				'netmask'			=> $subCul->getSubnetMask(),
				'network_address'	=> $subCul->getNetworkPortion(),
				'broadcast_address'	=> $subCul->getBroadcastAddress(),
				'usable_ip'			=> $subCul->getNumberAddressableHosts(),
				'first_usable_ip'	=> $subCul->getAddressableHostRange()[0],
				'last_usable_ip'	=> $subCul->getAddressableHostRange()[1]
			]
		];
		
		if($group_name != null)
			$data['group_name'] = $group_name;

		$newGroup = GroupIp::create($data);
		
		Flash::success($flash_message);

		return true;
	}
}