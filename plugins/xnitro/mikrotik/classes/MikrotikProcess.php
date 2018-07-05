<?php namespace Xnitro\Mikrotik\Classes;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Xnitro\Mikrotik\Models\MikrotikServer;
use PEAR2\Net\RouterOS;
use Log;
use ApplicationException;

class MikrotikProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $_routerConn = null;

    private $parameters = [];

    private $endpoint = null;

    private $requestType = 'add';

    public function createServer(&$parameters=[]){
    	if(isset($parameters['server_id'])){
    		$server_id = $parameters['server_id'];
    		unset($parameters['server_id']);
    	}else{
    		throw new ApplicationException('Server ID Not Found.');
    		return false;
    	}

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
		} catch (Exception $e) {
		    throw new ApplicationException('Error Connection to Mikrotik Server');
		}
    }

    public static function createPoolIp($parameters=[]){
    	$instance = new self();
    	$instance->parameters = $parameters;
    	$instance->endpoint = '/ip pool add';

    	return $instance;
    }

    public static function createProfileIp($parameters=[]){
    	$instance = new self();
    	$instance->parameters = $parameters;
    	$instance->endpoint = '/ppp profile add';
    	
    	return $instance;
    }

    public static function createSecretPPP($parameters=[]){
    	$instance = new self();
    	$instance->parameters = $parameters;
    	$instance->endpoint = '/ppp secret add';

    	return $instance;
    }

    public static function actionSecret($action='', $parameters=[]){
        $instance = new self();
        if(isset($parameters['name'])){
            $instance->parameters = $parameters;
            $instance->endpoint = '/ppp secret';
            $instance->requestType = $action;
        }

        return $instance;
    }

	public function createRequest($endpoint='', $parameters=[]){
        if(in_array($this->requestType, ['delete', 'enabled', 'disabled'])){
            $util = new RouterOS\Util($this->_routerConn);
            $util->setMenu($this->endpoint);
            $name = $parameters['name'];
            $id = $util->find(function($response)use($name){
                return preg_match('/^'.$name.'$/', $response->getProperty('name'));
            });
            switch ($this->requestType) {
                case 'delete':
                    return $util->remove($id);
                    break;
                case 'enabled':
                    return $util->enable($id);
                    break;
                case 'disabled':
                    return $util->disable($id);
                    break;
                default:
                    return false;
                    break;
            }
        }else{
            $setRequest = new RouterOS\Request($endpoint);
            if($parameters){
                foreach ($parameters as $key => $value) {
                    $setRequest->setArgument($key, $value);
                }
            }

            return $this->_routerConn->sendSync($setRequest);
        }
	}

    /**
     * Execute the job.
     *
     * @param  AudioProcessor  $processor
     * @return void
     */
    public function handle()
    {
    	if($this->endpoint && $this->parameters){
    		$parameters = $this->parameters;
    		$this->createServer($parameters);
    		$result = $this->createRequest($this->endpoint, $parameters);
    		if($result && $result->getType() == '!done'){
    			// proccess done
    		}else{
    			throw new ApplicationException('Error Execute');
    		}

    		$this->_routerConn->close();
    	}
    }
}