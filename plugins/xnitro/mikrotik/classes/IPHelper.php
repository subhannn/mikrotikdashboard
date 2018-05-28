<?php
namespace Xnitro\Mikrotik\Classes;

use Xnitro\Mikrotik\Models\GroupIp;
use Xnitro\Mikrotik\Models\PoolIp;
use IPv4\SubnetCalculator;
use BackendAuth;
use ApplicationException;
use Flash;

class IPHelper{
	use \October\Rain\Support\Traits\Singleton;

	const FIRST_GROUP_IP = '10.0.0.0';

	const GROUP_SIZE = 20;

	const POOL_SIZE = 28;

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
		$first_usable_ip = $subCul->getAddressableHostRange()[0];
		$first_usable_ip = long2ip(ip2long($first_usable_ip)+1);
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
				'usable_ip'			=> ($subCul->getNumberAddressableHosts()-1),
				'first_usable_ip'	=> $first_usable_ip,
				'last_usable_ip'	=> $subCul->getAddressableHostRange()[1]
			]
		];
		// print_r($data);
		// exit();
		
		if($group_name != null)
			$data['group_name'] = $group_name;

		$newGroup = GroupIp::create($data);
		
		Flash::success($flash_message);

		return true;
	}

	public function assignIpForm($pool_id=null, $return_number=false){
		if(!$pool_id){
			throw new ApplicationException('Pool ID Not Found');
			return false;
		}
		$pool = GroupIp::find($pool_id);
		if(!isset($pool->id)){
			throw new ApplicationException('Pool ID Not Found');
			return false;
		}

		$available_ip = $pool->meta['usable_ip'] - count($pool->pool_ip);
		if($available_ip <= 0 && !$return_number){
			throw new ApplicationException('Current Pool IP not Available');
			return false;
		}elseif($return_number){
			return $available_ip;
		}

		$data = [];
		
		$data['ips'] = range(1, $available_ip);
		
		return $data;
	}

	public function assignIp($pool_id, $number_ip, $user_id){
		if(!$pool_id){
			throw new ApplicationException('Pool ID Not Found');
			return false;
		}
		$pool = GroupIp::find($pool_id);
		if(!isset($pool->id)){
			throw new ApplicationException('Pool ID Not Found');
			return false;
		}

		$available_ip = $pool->meta['usable_ip'] - count($pool->pool_ip);
		if($available_ip <= 0){
			throw new ApplicationException('Current Pool IP not Available');
			return false;
		}
		if($number_ip > $available_ip){
			throw new ApplicationException('Only Available '.$available_ip.' IP on this Pool, please create manual new Pool.');
			return false;
		}
		$last_ip = $pool->pool_ip()->getLastIp()->first();
		if(!$last_ip){
			$last_ip = $pool->meta['first_usable_ip'];
		}else{
			$last_ip = long2ip(ip2long($last_ip->ip)+1);
		}
		// echo $last_ip;
		// exit();
		$createPool = [];
		foreach (range(1, $number_ip) as $num) {
			$last_ip = long2ip(ip2long($last_ip));
			$createPool = new PoolIp([
				'group_id'	=> $pool_id,
				'ip'		=> $last_ip
			]);
			$createPool->assign = [$user_id];
			$createPool->save();

			$last_ip = long2ip(ip2long($last_ip)+1);
		}

		Flash::success('Assign IP Success');

		return true;
	}
}