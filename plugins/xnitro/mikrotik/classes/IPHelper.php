<?php
namespace Xnitro\Mikrotik\Classes;

use Xnitro\Mikrotik\Models\GroupIp;
use IPv4\SubnetCalculator;
use BackendAuth;

class IPHelper{
	use \October\Rain\Support\Traits\Singleton;

	const FIRST_GROUP_IP = '10.0.0.0';

	const GROUP_SIZE = 20;

	public function requestNewGroupIp($parent_id=null, $size=self::GROUP_SIZE){
		if($parent_id != null){
			$parent = GroupIp::find($parent_id);
			if(!isset($parent->id))
				return false;

			$group = GroupIp::getLastGroup($parent->id)->first();
			if(!$group){ // create pool group
				$group_ip = $parent->ip;
			}else{
				$curLastIp = $group->last_ip;
				$group_ip = long2ip(ip2long($curLastIp)+1);
			}
		}else{
			$group = GroupIp::getLastGroup()->first();
			if(!$group){ // create group
				$group_ip = self::FIRST_GROUP_IP;
			}else{
				$curLastIp = $group->last_ip;
				$group_ip = long2ip(ip2long($curLastIp)+1);
			}
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
				'usable_ip'			=> $subCul->getNetworkSize(),
				'first_usable_ip'	=> $subCul->getAddressableHostRange()[0],
				'last_usable_ip'	=> $subCul->getAddressableHostRange()[1]
			]
		];
		$newGroup = GroupIp::create($data);
		
		print_r($newGroup);
		exit();
	}
}