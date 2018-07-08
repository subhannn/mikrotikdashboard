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

	public function requestNewPoolIp($data = []){
		extract(array_merge([
            'network_size'	=> null,
            'server_id' 	=> null,
            'user_id'		=> null,
            'expired_date'	=> null,
                        ], $data));

		if(!isset(self::NETWORK_IP[$network_size])){
			throw new ApplicationException('Network Size out of Size');
			return false;
		}

		if($expired_date == null){
			throw new ApplicationException('Set Expired Date First');
			return false;
		}

		$user = Auth::findUserById($user_id);
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
		
		// check release IP first
		$release = PoolIp::GetReleaseIp($server_id, $network_size)->first();
		$releaseIp = false;
		if($release){
			$releaseIp = true;
			$rootUserIp = $release->ip;
		}else{
			$last_pool_ip = PoolIp::GetLastIp($server_id, $network_size)->first();
			if($last_pool_ip){
				$rootUserIp = $this->nextIp($last_pool_ip->usable_last_ip, 2);
			}else{
				$rootUserIp = $firstRootIp;
			}
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
			'status'			=> 1,
			'active'			=> 1,
			'paid'				=> 1,
			'expired_date'		=> date("Y-m-d", strtotime($expired_date)),
		];
		
		$flash_message = 'Create Pool IP Success';
		if($releaseIp){
			$data['deleted_at'] = null;
			$release->update($data);
		}else{
			$newPoolIp = PoolIp::create($data);
		}

		// SERVER PROCESS
		// #Create POOL IP
		$t = $this->generateStrongPassword(3, false, 'lud');
		$range = $usableIpSize==1?$userIp:($userIp.'-'.$lastUserIp);
		$name = $user->username;

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
				return $this->getChildUserTunnel($pool_ip);
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
				if($pool_ip->active == '0')
					throw new ApplicationException('Your root account has been disabled, you cant run this operation.');

				// create new user
				$t = $this->generateStrongPassword(3, false, 'lud');
				$username = $pool_ip->user->username.'_'.$t;
				$password = $this->generateStrongPassword(10);

				$child = ChildUser::create([
					'pool_ip_id'	=> $pool_ip->id,
					'username'		=> $username,
					'password'		=> $password,
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

					return $this->getChildUserTunnel($pool_ip);
				}
			}

			return $data;
		}

		throw new ApplicationException('No User Login.');
	}

	public function actionUserTunnelChild($action='', $data=[], $actionToAll=false, $isAdmin=false){
		if(!isset($data['id']))
			throw new ApplicationException('Select 1 item for delete.');

		if(isset($data['type']) && $data['type'] == 'root'){
			$check = PoolIp::find($data['id']);
			if(!$isAdmin && $check->active == '0')
				throw new ApplicationException('Your root account has been disabled, you cant run this operation.');
		}else{
			$check = ChildUser::find($data['id']);
			if(!$isAdmin && $check->pool_ip->active == '0')
				throw new ApplicationException('Your root account has been disabled, you cant run this operation.');
		}
		
		if($check && in_array($action, ['delete', 'enabled', 'disabled', 'change_password'])){
			$server_id = (isset($data['type']) && $data['type']=='root')?$check->server_id:$check->pool_ip->server_id;
			$username = $actionToAll?$check->username.'.*':$check->username;

			$defaultParam = [
				'server_id'		=> $server_id,
				'name'  		=> $username,
			];
			if($action == 'change_password'){
				$defaultParam['password'] = $data['password'];
			}

			// action server
			MikrotikProcess::withChain([
				MikrotikProcess::actionSecret($action, $defaultParam),
			])->dispatch();

			switch ($action) {
				case 'change_password':
					$check->password = $data['password'];
					$check->save();
					break;
				case 'delete':
					$check->delete();
					break;
				case 'enabled':
					$check->active = 1;
					$check->save();
					break;
				case 'disabled':
					$check->active = 0;
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

	public function getActiveUserData(){
        if($this->user){
            // $data = array_only($user->attributes, ['id', 'name', 'surname', 'email', 'username']);
            $user_attributes = $this->user->attributes;
            $data = [
            	'id'		=> $this->user->id,
            	'fullname'	=> implode(' ', array_only($user_attributes, ['name', 'surname'])),
            	'email'		=> $user_attributes['email'],
            	'image'		=> ($this->user->avatar)?$this->user->avatar->getThumb(200, 200):null,
            	'permissions'=> $this->userPermissions()
            ];
            
            return $data;
        }

        return false;
	}

	public function removePoolIp($data=[]){
		if(!$data)
			throw new ApplicationException('Select at leat 1 item for delete.');

		$pools = PoolIp::whereIn('id', $data)->get();
		if($pools){
			$trashed = [];
			foreach ($pools as $row) {
				// action server
				MikrotikProcess::withChain([
					MikrotikProcess::actionSecret('delete', [
						'server_id'		=> $row->server_id,
						'name'  		=> $row->user->username.'.*',
					]),
					MikrotikProcess::actionProfile('delete', [
						'server_id'		=> $row->server_id,
						'name'  		=> $row->user->username.'_profile',
					]),
					MikrotikProcess::actionPoolIp('delete', [
						'server_id'		=> $row->server_id,
						'name'  		=> $row->user->username.'_pool',
					]),
				])->dispatch();

				$row->child_user()->delete();
				$row->delete();
			}

			PoolIp::onlyTrashed()->whereIn('id', $data)->update([
				'status'	=> 2
			]);
		}
	}

	private function getChildUserTunnel($pool_ip){
		$data = [];
		$max_child = (ip2long($pool_ip->usable_last_ip)-ip2long($pool_ip->ip));
		$child = $pool_ip->child_user()->AllChildUser()->toArray();
		$data['max_child_account'] = ($max_child - count($child));
		$data['child'] = $child;
		$root_account = array_only($pool_ip->attributes, ['id', 'username', 'password', 'created_at', 'last_logout', 'expired_date']);
		$root_account['status'] = $pool_ip->active;
		$root_account['host_ip'] = $pool_ip->server->host;
		// $root_account['host_port'] = $pool_ip->server->port;
		$data['root_account'] = $root_account;

		return $data;
	}

	public function userPermissions(){
		$data = [];

		if($this->user){
			// Check User Child Tunnel
			$user_child = PoolIp::where('user_id', $this->user->id)->first();
			if($user_child)
				$data[] = 'susbcribe_tunnel';

			if($user_child && $user_child->size != '30'){
				$data[] = 'child_user_manage';
			}
		}

		return $data;
	}

	private function checkLastLogout(){
		if($this->user){
			// $result = 
		}
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
}