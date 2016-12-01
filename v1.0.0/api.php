<?php

/*!
 *
 * SpryAPI Framework
 * https://github.com/ggedde/SpryAPI
 * Version 1.3.0
 *
 * Copyright 2016, GGedde
 * Released under the MIT license
 *
 */

class API {

	private static $routes = [];
	private static $params = [];
	private static $db;
	private static $path;
	private static $validator;
	private static $account_id=0;
	private static $access_key='';
	private static $config;
	private static $dir;
	
	
	/**
	 * Initiates the API Call.
 	 *
 	 * @access 'public'
 	 * @return void
 	 * @final
	 */

	final public static function run()
	{
		$config = new stdClass();
		require_once('config.php');
		self::$config = $config;

		if(!empty(self::$config->log_file))
		{
			set_error_handler(array(__CLASS__, 'error_handler'));
			register_shutdown_function(array(__CLASS__, 'shut_down_function'));
		}

		self::$path = self::get_path();

		spl_autoload_register(array(__CLASS__, 'autoloader'));

		self::$params = self::fetch_params();
		
		self::$dir = dirname(__FILE__);

		if(!empty(self::$config->pre_auth_filters) && is_array(self::$config->pre_auth_filters))
		{
			foreach (self::$config->pre_auth_filters as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}
		
		if(!empty(self::$config->db))
		{
			self::$db = new DB(self::$config->db);
		}
		
		if(!empty(self::$config->post_db_filters) && is_array(self::$config->post_db_filters))
		{
			foreach (self::$config->post_db_filters as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}

		self::check_auth(self::$path);

		self::set_routes();

		$route = self::get_route(self::$path);

		if(!empty(self::$config->post_auth_filters) && is_array(self::$config->post_auth_filters))
		{
			foreach (self::$config->post_auth_filters as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}

		$controller = self::get_controller($route['controller']);

		$response = self::get_response($controller);

		self::send_response($response);
	}




	/**
	 * Adds all the Routes to allow.
 	 *
 	 * @access 'private'
 	 * @return void
 	 * @final
	 */

	final private static function set_routes()
	{
		foreach (self::$config->routes as $route_url => $route_class)
		{
			self::add_route($route_url, $route_class);
		}
	}



	/**
	 * Gets the response Type.
 	 *
 	 * @access 'private'
 	 * @return string
 	 * @final
	 */

	final private static function response_type($code='')
	{
		if(!empty($code) && is_numeric($code))
		{
			switch (substr($code, 0, 1))
			{
				case 2:
				case 4:
					return 'success';

				break;


				case 5:
					return 'error';

				break;

			}
		}

		return 'unknown';
	}



	/**
	 * Sets all the response Codes available for the App.
 	 *
 	 * @access 'private'
 	 * @return array
 	 * @final
	 */

	final private static function response_codes($code='')
	{
		$lang = 'en';
		$type = 4;

		if(self::params('lang'))
		{
			$lang = self::params('lang');
		}

		if(strlen($code))
		{
			$type = substr($code, 0, 1);
		}

		if(isset(self::$config->response_codes[$code][$lang]))
		{
			$response = self::$config->response_codes[$code][$lang];
		}
		else if(isset(self::$config->response_codes[$code]['en']))
		{
			$response = self::$config->response_codes[$code]['en'];
		}
		else if(isset(self::$config->response_codes[$code]) && is_string(self::$config->response_codes[$code]))
		{
			$response = self::$config->response_codes[$code];
		}
		else if($type === 4 && isset(self::$config->response_codes[4000][$lang]))
		{
			$code = 4000;
			$response = self::$config->response_codes[$code][$lang];
		}
		else if($type === 4 && isset(self::$config->response_codes[4000]['en']))
		{
			$code = 4000;
			$response = self::$config->response_codes[$code]['en'];
		}
		else if($type === 4 && isset(self::$config->response_codes[4000]) && is_string(self::$config->response_codes[4000]))
		{
			$code = 4000;
			$response = self::$config->response_codes[$code];
		}

		if(!empty($response))
		{
			return ['response' => self::response_type($code), 'response_code' => $code, 'messages' => [$response]];
		}

		return ['response' => 'unknown', 'response_code' => $code, 'messages' => ['Unkown Response Code']];
	}



	/**
	 * Adds a route to the allowed list.
 	 *
 	 * @param string $path
 	 * @param string $controller
 	 *
 	 * @access 'private'
 	 * @return void
 	 * @final
	 */

	final private static function add_route($path, $controller)
	{
		$path = self::clean_path($path);
		self::$routes[$path] = $controller;
	}



	/**
	 * Returns the Route including path and attached controller.
 	 *
 	 * @param string $path
 	 *
 	 * @access 'protected'
 	 * @return array
 	 * @final
	 */

	final protected static function get_route($path=null)
	{
		if(!$path)
		{
			$path = self::$path;
		}

		$path = self::clean_path($path);

		if(!empty($path) && !empty(self::$routes[$path]))
		{
			return ['path' => $path, 'controller' => self::$routes[$path]];
		}

		self::stop_error(5102); // Request Not Found
	}



	/**
	 * Sets the error handler for all PHP errors in the App.
 	 *
 	 * @param int $errno
 	 * @param string $errstr
 	 * @param string $errfile
 	 * @param string $errline
 	 *
 	 * @access 'public'
 	 * @return void
 	 * @final
	 */

	final protected static function error_handler($errno, $errstr, $errfile, $errline)
	{
		if(!empty($errstr))
		{
			if(strpos($errstr, '[SQL Error]') !== false)
			{
				$errno = 'SQL Error';
			}

			switch ($errno)
			{
			    case E_ERROR:
			        $errstr = 'PHP Fatal Error: '.$errstr;
			        break;

			    case 'SQL Error':
			        $errstr = 'SQL Error: '.str_replace('[SQL Error] ', '', $errstr);
			        break;

			    case E_WARNING:
			    case E_USER_WARNING:
			        $errstr = 'PHP Warning: '.$errstr;
			        break;

			    case E_NOTICE:
			    case E_USER_NOTICE:
			    case '8':
			        $errstr = 'PHP Notice: '.$errstr;
			        break;

			    default:
			    	$errstr = 'PHP Unknown Error: '.$errstr;
			        break;
	    	}

		    $backtrace = '';
		    $dbts = debug_backtrace();
			foreach ($dbts as $dbt)
			{
				if(!empty($dbt['file']))
				{
					$backtrace.= ' - - Trace: '.$dbt['file'].' [Line: '.(!empty($dbt['line']) ? $dbt['line'] : '?').'] - Function: '.$dbt['function']."\n";
				}
			}

			$data = $errstr.$errfile.' [Line: '.(!empty($errline) ? $errline : '?')."]\n".$backtrace;

			// Try and Make directory if id doesn't exist
			if(!is_dir(dirname(self::$config->log_file)))
			{
				mkdir(dirname(self::$config->log_file));
			}

			file_put_contents(self::$config->log_file, $data, FILE_APPEND);
		}
	}



	/**
	 * Checks the API on Shutdown for Fatal Errors.
 	 *
 	 * @access 'public'
 	 * @return void
 	 * @final
	 */

	final protected static function shut_down_function()
	{
	    $error = error_get_last();
	    if ($error['type'] === E_ERROR)
	    {
	        self::error_handler($error['type'], $error['message'], $error['file'], $error['line']);
	    }
	}



	/**
	 * Sets the Autoloader for the Extra Classed needed for the API.
 	 *
 	 * @access 'public'
 	 * @return void
 	 * @final
	 */

	final protected static function autoloader($class)
	{
		$controller_file = __DIR__.'/controllers/'.strtolower($class).'.php';
		$extension_file = __DIR__.'/extensions/'.strtolower($class).'.php';

		if(file_exists($controller_file))
		{
			require_once $controller_file;
		}
		else if(file_exists($extension_file))
		{
			require_once $extension_file;
		}
	}



	/**
	 * Returns DB Extension.
 	 *
 	 * @access 'protected'
 	 * @return object
 	 * @final
	 */

	final protected static function db()
	{
		return self::$db;
	}



	/**
	 * Returns Validator Extension.
 	 *
 	 * @access 'protected'
 	 * @return object
 	 * @final
	 */

	final protected static function validator($params=array())
	{
		if(empty($params))
		{
			$params = self::$params;
		}

		if(empty(self::$validator))
		{
			self::$validator = new Validator($params);
		}
		else
		{
			self::$validator->setData($params);
		}

		return self::$validator;
	}



	/**
	 * Kills the Request and returns immediate error.
 	 *
 	 * @param int $response_code
 	 * @param mixed $data
 	 *
 	 * @access 'protected'
 	 * @return void
 	 * @final
	 */

	final protected static function stop_error($response_code=0, $data=null, $messages=[])
	{
		if(!empty(self::$config->stop_error_filters) && is_array(self::$config->stop_error_filters))
		{
			$params = [
				'response_code' => $response_code,
				'data' => $data,
				'messages' => $messages
			];

			foreach (self::$config->stop_error_filters as $filter)
			{
				self::get_response(self::get_controller($filter), $params);
			}
		}
		
		$response = self::build_response($response_code, $data, $messages);
		self::send_output($response);
	}



	/**
	 * Checks the API call for Authentication.
	 * Throughs stop_error() on failure.
	 *
	 * @param string $path
 	 *
 	 * @access 'private'
 	 * @return void
 	 * @final
	 */

	final private static function check_auth($path)
	{
		if($path !== '/auth/get/')
		{
			$access_key = self::params('access_key');
			$account_id = AUTH::get_account_id();

			if($access_key && $account_id)
			{
				self::$access_key = $access_key;
				self::$account_id = $account_id;
				return true;
			}
			else
			{
				sleep(5); // Reduce Hack attempts
				self::stop_error(5201);
			}
		}
	}



	/**
	 * Returns the Account ID from the API Call.
 	 *
 	 * @access 'protected'
 	 * @return int
 	 * @final
	 */

	final protected static function account_id()
	{
		return self::$account_id;
	}
	
	
	/**
	 * Returns the Access Key from the API Call.
 	 *
 	 * @access 'protected'
 	 * @return string
 	 * @final
	 */

	final protected static function access_key()
	{
		return self::$access_key;
	}


	/**
	 * Returns the Config Parameters from the Singleton Class.
 	 *
 	 * @access 'protected'
 	 * @return object
 	 * @final
	 */

	final protected static function config()
	{
		return self::$config;
	}
	
	
	
	/**
	 * Returns the Root Directory of the API.
 	 *
 	 * @access 'protected'
 	 * @return object
 	 * @final
	 */

	final static protected function dir()
	{
		return self::$dir;
	}



	/**
	 * Gets the Data sent in the API Call and converts it to Parameters.
	 * Then returns the converted Parameters as array.
	 * Throughs stop_error() on failure.
 	 *
 	 * @access 'private'
 	 * @return array
 	 * @final
	 */

	final private static function fetch_params($param='')
	{
		if($data = trim(file_get_contents('php://input')))
		{
			if(strpos($data, '{') !== false)
			{
				$data = json_decode($data, true);
			}
			else
			{
				echo '<pre>';print_r($data);echo '</pre>';
				exit;
			}
		}



		if(!empty($data) && !is_array($data))
		{
			self::stop_error(5104); // Returned Data is not in JSON format
		}

		if(!empty($data) && is_array($data))
		{
			if($param)
			{
				if(isset($data[$param]))
				{
					return $data[$param];
				}

				return null;
			}

			return $data;
		}

		self::stop_error(5101); // No Parameters Found
	}




	/**
	 * Gets the Data sent in the API Call and converts it to Parameters.
	 * Then returns the converted Parameters as array.
	 * Throughs stop_error() on failure.
 	 *
 	 * @access 'private'
 	 * @return array
 	 * @final
	 */

	final protected static function params($param='')
	{
		if($param)
		{
			// Check for Multi-Demension Parameter
			if(strpos($param, '.'))
			{
				$nested_param = self::$params;
				$param_items = explode('.', $param);
				foreach ($param_items as $param_items_key => $param_item)
				{
					if($nested_param !== null && isset($nested_param[$param_item]))
					{
						$nested_param = $nested_param[$param_item];
					}
					else
					{
						$nested_param = null;
					}
				}

				return $nested_param;
			}

			if(isset(self::$params[$param]))
			{
				return self::$params[$param];
			}

			return null;
		}

		return self::$params;
	}



	/**
	 * Gets the URL Path of the current API Call.
 	 *
 	 * @access 'protected'
 	 * @return string
 	 * @final
	 */

	final protected static function get_path()
	{
		$path = explode('?', strtolower($_SERVER['REQUEST_URI']), 2);
		return self::clean_path($path[0]);
	}



	/**
	 * Cleans the Path given to a specified format.
 	 *
 	 * @access 'private'
 	 * @return string
 	 * @final
	 */

	final private static function clean_path($path)
	{
		if(substr($path, -1) === '/')
		{
			return trim($path);
		}
		return trim($path).'/';
	}



	/**
	 * Returns the Controller Object and Method by name.
	 * Throughs stop_error() on failure.
	 *
	 * @param string $controller_name
 	 *
 	 * @access 'private'
 	 * @return array
 	 * @final
	 */

	final private static function get_controller($controller_name='')
	{
		if(!empty($controller_name))
		{
			list($class, $method) = explode('::', $controller_name);

			$obj = new $class;

			if($obj)
			{
				if(method_exists($obj, $method))
				{
					return ['obj' => $obj, 'method' => $method];
				}
				self::stop_error(5105, null, $controller_name); // Method Not Found
			}
		}

		self::stop_error(5103, null, $controller_name); // Controller Not Found
	}



	/**
	 * Creates a one way Hash value used for Passwords and other authentication.
	 *
 	 * @param string $value
 	 *
 	 * @access 'private'
 	 * @return string
	 */

	final protected static function hash($value)
	{
		$salt = '';

		if(!empty(self::$config->salt))
		{
			$salt = self::$config->salt;
		}

		return md5(serialize($value).$salt);
	}
	
	
	
	/**
	 * Return just the body of the request is successfull.
	 *
 	 * @param string $result
 	 *
 	 * @access 'protected'
 	 * @return mixed
	 */

	final protected static function get_body($result)
	{
		if(!empty($result['response']) && $result['response'] === 'success' && isset($result['body']))
		{
			return $result['body'];
		}

		return null;
	}



	/**
	 * Formats the Results given by a Controller method.
	 *
	 * @param int $response_code
	 * @param mixed $data
 	 *
 	 * @access 'protected'
 	 * @return array
 	 * @final
	 */

	final protected static function results($response_code=0, $data=null, $messages=[])
	{
		if(strlen(strval($response_code)) > 3)
		{
			return self::build_response($response_code, $data, $messages);
		}

		if(empty($data) && $data !== null && (empty(self::$db) || !self::$db->has_error()))
		{
			return self::build_response('4' . $response_code, $data, $messages);
		}

		if(!empty($data))
		{
			return self::build_response('2' . $response_code, $data, $messages);
		}

		return self::build_response('5' . $response_code, null, $messages);
	}



	/**
	 * Formats the Response before given to the Output Method
	 *
	 * @param int $response_code
	 * @param mixed $data
 	 *
 	 * @access 'protected'
 	 * @return array
 	 * @final
	 */

	final private static function build_response($response_code=0, $data=null, $messages=[])
	{
		$response = self::response_codes($response_code);

		if($data !== null)
		{
			$response['body_hash'] = md5(serialize($data));
			$response['body'] = $data;
		}

		if(!empty($messages) && (is_string($messages) || is_numeric($messages)))
		{
			$messages = [$messages];
		}

		if(!empty($messages))
		{
			$response['messages'] = array_merge($response['messages'], $messages);
		}

		return $response;
	}



	/**
	 * Returns the Response from a given Controller method
	 *
	 * @param array $controller
 	 *
 	 * @access 'private'
 	 * @return mixed
 	 * @final
	 */

	final static private function get_response($controller=array(), $params=null)
	{
		if(!is_callable(array($controller['obj'], $controller['method'])))
		{
			self::stop_error(5106, null, $controller['method']);
		}

		if($params)
		{
			return call_user_func(array($controller['obj'], $controller['method']), $params);
		}

		return call_user_func(array($controller['obj'], $controller['method']));
	}



	/**
	 * Formats the Response and Sends
	 * it to the Output Method.
	 *
	 * @param array $response
 	 *
 	 * @access 'private'
 	 * @return void
 	 * @final
	 */

	final private static function send_response($response=array())
	{
		if(empty($response['response']) || empty($response['response_code']))
		{
			$response = self::build_response('', $response);
		}
		self::send_output($response);
	}



	/**
	 * Formats the Response for output and
	 * sets the appropriate headers.
	 *
	 * @param array $output
 	 *
 	 * @access 'protected'
 	 * @return void
 	 * @final
	 */

	final private static function send_output($output=array())
	{
		header("Access-Control-Allow-Origin: *");
	    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
	    header("Access-Control-Allow-Headers: X-Requested-With, content-type");

		echo json_encode($output);
		exit;
	}

}
