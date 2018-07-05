<?php
namespace Xnitro\Mikrotik\Classes;

// use Xnitro\Mikrotik\Models\GroupIp;
use Xnitro\Mikrotik\Models\PoolIp;
use IPv4\SubnetCalculator;
use BackendAuth;
use ApplicationException;
use Flash;
use Xnitro\Mikrotik\Models\Settings;
use Auth;
use Xnitro\Mikrotik\Models\MikrotikServer;
use Xnitro\Mikrotik\Models\ChildUser;
use Xnitro\Mikrotik\Classes\Server;
use Xnitro\Mikrotik\Classes\MikrotikProcess;

class IPHelper{
	use \October\Rain\Support\Traits\Singleton;

	const FIRST_GROUP_IP = '10.0.0.0';

	const GROUP_SIZE = 20;

	const POOL_SIZE = 28;

	const NETWORK_IP = [
		'30'	=> '10.10.0.0',
		'29'	=> '10.11.0.0',
		'28'	=> '10.12.0.0'
	];

	private $settings = null;

	private $user = null;

	public function init(){
		$this->settings = Settings::instance();
		$user = Auth::getUser();
		if($this->user == null && $user){
			$this->user = $user;
		}
	}

	public function requestNewPoolIp($network_size, $user, $server_id){
		if(!isset(self::NETWORK_IP[$network_size])){
			throw new ApplicationException('Network Size out of Size');
			return false;
		}

		$user = Auth::findUserById($user);
		if(!$user){
			throw new ApplicationException('User not found or user is suspended.');
			return false;
		}

		// Check Existing IP Same Network Size
		$checkUser = PoolIp::CheckUser($server_id, $network_size, $user->id)->first();
		if($checkUser){
			throw new ApplicationException('User ('.$user->name.' '.$user->surname.') already subscribe /'.$checkUser->size.', Please upgrade to have much IP.');
			return false;
		}
		
		$rootIp = self::NETWORK_IP[$network_size];
		$rootCal = new SubnetCalculator($rootIp, '16');
		$firstRootIp = $this->nextIp($rootCal->getAddressableHostRange()[0]);
		$lastRootIp = $rootCal->getAddressableHostRange()[1];
		// $release = PoolIp::getReleaseIp($server, $network_size);
		// if($release->count() > 0){

		// }

		$last_pool_ip = PoolIp::GetLastIp($server_id, $network_size)->first();
		if($last_pool_ip){
			$rootUserIp = $this->nextIp($last_pool_ip->usable_last_ip, 2);
		}else{
			$rootUserIp = $firstRootIp;
		}

		$userCal = new SubnetCalculator($rootUserIp, $network_size);
		$firstUserIp = $userIp = $this->nextIp($userCal->getAddressableHostRange()[0]);
		$lastUserIp = $userCal->getAddressableHostRange()[1];
		$usableIpSize = ($userCal->getNumberAddressableHosts()-1);
		if($usableIpSize > 1){
			$firstUserIp = $this->nextIp($firstUserIp);
		}

		$userUsername = $user->username;
		$userPassword = $this->generateStrongPassword(10);
		$data = [
			'ip'				=> $userIp,
			'usable_first_ip'	=> $firstUserIp,
			'usable_last_ip'	=> $lastUserIp,
			'size'				=> $network_size,
			'user_id'			=> $user->id,
			'username'			=> $userUsername,
			'password'			=> $userPassword,
			'server_id'			=> $server_id,
			'status'			=> 1
		];
		
		$flash_message = 'Create Pool IP Success';
		$newPoolIp = PoolIp::create($data);

		// SERVER PROCESS
		// #Create POOL IP
		$t = $this->generateStrongPassword(3, false, 'lud');
		$range = $usableIpSize==1?$userIp:($userIp.'-'.$lastUserIp);
		$name = $user->username;

		// $server = Server::instance()->makeServer($server_id);
		// $server->createPoolIp($name, $range);

		MikrotikProcess::withChain([
			MikrotikProcess::createPoolIp([
				'server_id'	=> $server_id,
				'name'  => $name.'_pool',
	            'ranges'=> $range
			]),
			MikrotikProcess::createProfileIp([
				'server_id'		=> $server_id,
				'name'  		=> $name.'_profile',
	            'local-address'	=> $userCal->getAddressableHostRange()[0],
	            'remote-address'=> $name.'_pool'
			]),
			MikrotikProcess::createSecretPPP([
				'server_id'		=> $server_id,
				'name'  		=> $user->username,
	            'profile'		=> $name.'_profile',
	            'password'		=> $userPassword
			]),
		])->dispatch();

		Flash::success($flash_message);

		return true;
	}

	public function getTunnelIpList(){
		if($this->user){
			$pool_ip = PoolIp::where('user_id', $this->user->id)->first();
			$data = [
				'root_account'		=> [],
				'max_child_account'	=> 0,
				'child'				=> []
			];
			if($pool_ip){
				$max_child = (ip2long($pool_ip->usable_last_ip)-ip2long($pool_ip->ip));
				$child = $pool_ip->child_user()->AllChildUser()->toArray();
				$data['max_child_account'] = ($max_child - count($child));
				$data['child'] = $child;
				$data['root_account'] = array_only($pool_ip->attributes, ['id', 'username', 'password', 'status', 'created_at']);
			}

			return $data;
		}
	}

	public function createUserTunnelChild(){
		if($this->user){
			$pool_ip = PoolIp::where('user_id', $this->user->id)->first();
			$data = [
				'root_account'		=> [],
				'max_child_account'	=> 0,
				'child'				=> []
			];
			if($pool_ip){
				// create new user
				$t = $this->generateStrongPassword(3, false, 'lud');
				$username = $pool_ip->user->username.'_'.$t;
				$password = $this->generateStrongPassword(10);

				$child = ChildUser::create([
					'pool_ip_id'	=> $pool_ip->id,
					'user'			=> $username,
					'pass'			=> $password,
					'status'		=> 1
				]);

				if($child){
					// create on server
					MikrotikProcess::withChain([
						MikrotikProcess::createSecretPPP([
							'server_id'		=> $pool_ip->server_id,
							'name'  		=> $username,
				            'profile'		=> $pool_ip->user->username.'_profile',
				            'password'		=> $password
						]),
					])->dispatch();

					$max_child = (ip2long($pool_ip->usable_last_ip)-ip2long($pool_ip->ip));
					$child = $pool_ip->child_user()->AllChildUser()->toArray();
					$data['max_child_account'] = ($max_child - count($child));
					$data['child'] = $child;
					$data['root_account'] = array_only($pool_ip->attributes, ['id', 'username', 'password', 'status', 'created_at']);
				}
			}

			return $data;
		}

		throw new ApplicationException('No User Login.');
	}

	public function actionUserTunnelChild($action='', $data=[]){
		if(!isset($data['id'])){
			throw new ApplicationException('Select 1 item for delete.');
		}

		$check = ChildUser::find($data['id']);
		
		if($check && in_array($action, ['delete', 'enabled', 'disabled'])){
			// action server
			MikrotikProcess::withChain([
				MikrotikProcess::actionSecret($action, [
					'server_id'		=> $check->pool_ip->server_id,
					'name'  		=> $check->user,
				]),
			])->dispatch();

			switch ($action) {
				case 'delete':
					$check->delete();
					break;
				case 'enabled':
					$check->status = 1;
					$check->save();
					break;
				case 'disabled':
					$check->status = 0;
					$check->save();
					break;
				default:
					break;
			}

			return [
				'success'	=> true
			];
		}

		return [
			'success'	=> false
		];
	}

	private function nextIp($ip, $next=1){
		return long2ip(ip2long($ip)+$next);
	}

	public function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds')
	{
		$sets = array();
		if(strpos($available_sets, 'l') !== false)
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if(strpos($available_sets, 'u') !== false)
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if(strpos($available_sets, 'd') !== false)
			$sets[] = '23456789';
		if(strpos($available_sets, 's') !== false)
			$sets[] = '!@#$';
		$all = '';
		$password = '';
		foreach($sets as $set)
		{
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}
		$all = str_split($all);
		for($i = 0; $i < $length - count($sets); $i++)
			$password .= $all[array_rand($all)];
		$password = str_shuffle($password);
		if(!$add_dashes)
			return $password;
		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while(strlen($password) > $dash_len)
		{
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}
	
	// public function requestNewGroupIp($parent_id=null, $size=self::GROUP_SIZE){
	// 	$group_name = null;
	// 	if($parent_id != null){
	// 		$parent = GroupIp::find($parent_id);
	// 		if(!isset($parent->id)){
	// 			throw new ApplicationException('Parent ID Not Found');
	// 			return false;
	// 		}

	// 		$group = GroupIp::getLastGroup($parent->id)->first();
	// 		if(!$group){ // create pool group
	// 			$group_ip = $parent->ip;
	// 		}else{
	// 			$curLastIp = $group->last_ip;
	// 			$group_ip = long2ip(ip2long($curLastIp)+1);
	// 		}
	// 		if(ip2long($group_ip) > ip2long($parent->meta['broadcast_address'])){
	// 			throw new ApplicationException('All Pool IP is Sold, Please request new Group IP');
	// 			return false;
	// 		}
	// 		$group_name = BackendAuth::getUser()->login.'_pool_'.uniqid();
	// 		$flash_message = 'Create Pool IP Success';
	// 	}else{
	// 		$group = GroupIp::getLastGroup()->first();
	// 		if(!$group){ // create group
	// 			$group_ip = self::FIRST_GROUP_IP;
	// 		}else{
	// 			$curLastIp = $group->last_ip;
	// 			$group_ip = long2ip(ip2long($curLastIp)+1);
	// 		}
	// 		$subCul = new SubnetCalculator($group_ip, $size);
	// 		$flash_message = 'Create Group IP Success';
	// 		$parent_id = 0;
	// 	}

	// 	$subCul = new SubnetCalculator($group_ip, $size);
	// 	$first_usable_ip = $subCul->getAddressableHostRange()[0];
	// 	$first_usable_ip = long2ip(ip2long($first_usable_ip)+1);
	// 	$data = [
	// 		'ip'		=> (string) $group_ip,
	// 		'size'		=> $size,
	// 		'user_id'	=> BackendAuth::getUser()->id,
	// 		'last_ip'	=> $subCul->getBroadcastAddress(),
	// 		'parent_id'	=> $parent_id,
	// 		'meta'		=> [
	// 			'netmask'			=> $subCul->getSubnetMask(),
	// 			'network_address'	=> $subCul->getNetworkPortion(),
	// 			'broadcast_address'	=> $subCul->getBroadcastAddress(),
	// 			'usable_ip'			=> ($subCul->getNumberAddressableHosts()-1),
	// 			'first_usable_ip'	=> $first_usable_ip,
	// 			'last_usable_ip'	=> $subCul->getAddressableHostRange()[1]
	// 		]
	// 	];
	// 	// print_r($data);
	// 	// exit();
		
	// 	if($group_name != null)
	// 		$data['group_name'] = $group_name;

	// 	$newGroup = GroupIp::create($data);
		
	// 	Flash::success($flash_message);

	// 	return true;
	// }

	// public function assignIpForm($pool_id=null, $return_number=false){
	// 	if(!$pool_id){
	// 		throw new ApplicationException('Pool ID Not Found');
	// 		return false;
	// 	}
	// 	$pool = GroupIp::find($pool_id);
	// 	if(!isset($pool->id)){
	// 		throw new ApplicationException('Pool ID Not Found');
	// 		return false;
	// 	}

	// 	$available_ip = $pool->meta['usable_ip'] - count($pool->pool_ip);
	// 	if($available_ip <= 0 && !$return_number){
	// 		throw new ApplicationException('Current Pool IP not Available');
	// 		return false;
	// 	}elseif($return_number){
	// 		return $available_ip;
	// 	}

	// 	$data = [];
		
	// 	$data['ips'] = range(1, $available_ip);
		
	// 	return $data;
	// }

	// public function assignIp($pool_id, $number_ip, $user_id){
	// 	if(!$pool_id){
	// 		throw new ApplicationException('Pool ID Not Found');
	// 		return false;
	// 	}
	// 	$pool = GroupIp::find($pool_id);
	// 	if(!isset($pool->id)){
	// 		throw new ApplicationException('Pool ID Not Found');
	// 		return false;
	// 	}

	// 	$available_ip = $pool->meta['usable_ip'] - count($pool->pool_ip);
	// 	if($available_ip <= 0){
	// 		throw new ApplicationException('Current Pool IP not Available');
	// 		return false;
	// 	}
	// 	if($number_ip > $available_ip){
	// 		throw new ApplicationException('Only Available '.$available_ip.' IP on this Pool, please create manual new Pool.');
	// 		return false;
	// 	}
	// 	$last_ip = $pool->pool_ip()->getLastIp()->first();
	// 	if(!$last_ip){
	// 		$last_ip = $pool->meta['first_usable_ip'];
	// 	}else{
	// 		$last_ip = long2ip(ip2long($last_ip->ip)+1);
	// 	}
	// 	// echo $last_ip;
	// 	// exit();
	// 	$createPool = [];
	// 	foreach (range(1, $number_ip) as $num) {
	// 		$last_ip = long2ip(ip2long($last_ip));
	// 		$createPool = new PoolIp([
	// 			'group_id'	=> $pool_id,
	// 			'ip'		=> $last_ip
	// 		]);
	// 		$createPool->assign = [$user_id];
	// 		$createPool->save();

	// 		$last_ip = long2ip(ip2long($last_ip)+1);
	// 	}

	// 	Flash::success('Assign IP Success');

	// 	return true;
	// }
}