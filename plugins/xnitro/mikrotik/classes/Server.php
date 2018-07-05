<?php
namespace Xnitro\Mikrotik\Classes;

use IPv4\SubnetCalculator;
use BackendAuth;
use ApplicationException;
use Flash;
use PEAR2\Net\RouterOS;
use Exception;
use Xnitro\Mikrotik\Models\Settings;
use Xnitro\Mikrotik\Models\MikrotikServer;
use Xnitro\Mikrotik\Classes\MikrotikProcess;

class Server{
	use \October\Rain\Support\Traits\Singleton;

	private $_routerConn = null;

	public function makeServer($server_id){
		try {
			$server = MikrotikServer::find($server_id);
			if(!$server){
				throw new ApplicationException('Not Found Mikrotik Server');
				return false;
			}

			$this->_routerConn = new RouterOS\Client(
				$server->host, 
				$server->user, 
				$server->pass,
				$server->port
			);

			return $this;
		} catch (Exception $e) {
		    throw new ApplicationException('Error Connection to Mikrotik Server');
		}
	}

	public function createRequest($endpoint='', $parameters=[]){
		$setRequest = new RouterOS\Request($endpoint);
		if($parameters){
			foreach ($parameters as $key => $value) {
				$setRequest->setArgument($key, $value);
			}
		}
		return $this->_routerConn->sendSync($setRequest);
	}

	public function createPoolIp($name, $range_ip){
		try{
			$result = $this->createRequest('/ip pool add', [
	            'name'  => $name,
	            'ranges'=> $range_ip
	        ]);

	        return $result;
		}catch(Exception $e){
			throw new ApplicationException('Error create Pool IP');
		}
	}
}