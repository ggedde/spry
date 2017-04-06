<?php

/*!
 *
 * SpryAPI Framework
 * https://github.com/ggedde/SpryAPI
 * Version 2.0.0
 *
 * Copyright 2016, GGedde
 * Released under the MIT license
 *
 */

class SpryApi {

	private static $routes = [];
	private static $params = [];
	private static $db = null;
	private static $path;
	private static $validator;
	private static $auth;
	private static $config;

	/**
	 * Initiates the API Call.
	 *
	 * @param string $config_file
 	 *
 	 * @access 'public'
 	 * @return void
	 */

	public static function run($config_file='')
	{
		if(empty($config_file) || !file_exists($config_file))
		{
			self::stop(5001, null, ['Error: Missing Config File']);
		}

		self::load_config($config_file);

		spl_autoload_register(array(__CLASS__, 'autoloader'));

		// Configure Filters
		if(!empty(self::$config->hooks->configure) && is_array(self::$config->hooks->configure))
		{
			foreach (self::$config->hooks->configure as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}

		self::check_tools();

		self::$path = self::get_path();

		self::$params = self::fetch_params();

		// Param Filters
		if(!empty(self::$config->hooks->params) && is_array(self::$config->hooks->params))
		{
			foreach (self::$config->hooks->params as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}

		if(!empty(self::$config->db))
		{
			self::$db = new SpryApiDB(self::$config->db);
		}

		// Database Filters
		if(!empty(self::$config->hooks->database) && is_array(self::$config->hooks->database))
		{
			foreach (self::$config->hooks->database as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}

		self::set_routes();

		$route = self::get_route(self::$path);

		// Route Filters
		if(!empty(self::$config->hooks->routes) && is_array(self::$config->hooks->routes))
		{
			foreach (self::$config->hooks->routes as $filter)
			{
				self::get_response(self::get_controller($filter));
			}
		}

		$controller = self::get_controller($route['controller']);

		$response = self::get_response($controller);

		self::send_response($response);
	}

	protected static function load_config($config_file='')
	{
		$config = new stdClass();
		$config->hooks = new stdClass();
		$config->db = new stdClass();
		require_once($config_file);

		$config->response_codes = array_replace(self::get_core_response_codes(), (!empty($config->response_codes) ? $config->response_codes : []));
		self::$config = $config;
	}

	private static function get_core_response_codes()
	{
		return [
			4000 => ['en' => 'No Results Found'],
			5000 => ['en' => 'Error: Unknown Error'],
			5001 => ['en' => 'Error: Missing Config File'],

			5010 => ['en' => 'Error: No Parameters Found.'],
			5011 => ['en' => 'Error: Request Not Found.'],
			5012 => ['en' => 'Error: Controller Not Found.'],
			5013 => ['en' => 'Error: Controllers Method Not Found.'],
			5014 => ['en' => 'Error: Returned Data is not in JSON format.'],
			5015 => ['en' => 'Error: Controller Method is not Callable. Make sure it is Public.'],

			5020 => ['en' => 'Error: Field did not Validate.'],

			/* DB */
			2030 => ['en' => 'DB Migrate Ran Successfully'],
			5030 => ['en' => 'Error: DB Migrate had an Error'],

			/* Tests */
			2050 => ['en' => 'All Tests Passed Successfully'],
			5050 => ['en' => 'Error: Some Tests Failed'],
			5051 => ['en' => 'Error: Retrieving Tests'], //5500
			5052 => ['en' => 'Error: No Tests Configured'],

		];
	}


	private static function check_tools()
	{
		$controller = self::get_controller('SpryApiWebTools::display');
		$response = self::get_response($controller);
	}


	/**
	 * Adds all the Routes to allow.
 	 *
 	 * @access 'private'
 	 * @return void
	 */

	private static function set_routes()
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
	 */

	private static function response_type($code='')
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
	 */

	private static function response_codes($code='')
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
	 */

	private static function add_route($path, $controller)
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
	 */

	protected static function get_route($path=null)
	{
		if(!$path)
		{
			$path = self::$path;
		}

		$path = self::clean_path($path);

		if(!empty($path) && !empty(self::$routes[$path]))
		{
			$route = ['path' => $path, 'controller' => self::$routes[$path]];

			if(!empty(self::$config->hooks->get_route) && is_array(self::$config->hooks->get_route))
			{
				foreach (self::$config->hooks->get_route as $filter)
				{
					$route = self::get_response(self::get_controller($filter), $route);
				}
			}

			return $route;
		}

		self::stop(5011); // Request Not Found
	}



	/**
	 * Sets the Autoloader for the Extra Classed needed for the API.
 	 *
 	 * @access 'public'
 	 * @return void
	 */

	protected static function autoloader($class)
	{
		if(empty(self::$config->autoloader_directories))
		{
			self::$config->autoloader_directories = [];
		}

		// Add SpryApi Extensions to directories
		self::$config->autoloader_directories[] = __DIR__.'/extensions';

		if(!empty(self::$config->autoloader_directories))
		{
			foreach(self::$config->autoloader_directories as $dir)
			{
				foreach(glob(rtrim($dir, '/') . '/*')  as $file)
				{
					if(strtolower($class).'.php' === strtolower(basename($file)))
					{
						require_once $file;
						return;
					}
				}
			}
		}
	}


	/**
	 * Returns DB Extension.
 	 *
 	 * @access 'protected'
 	 * @return object
	 */

	protected static function db()
	{
		return self::$db;
	}



	/**
	 * Returns Validator Extension.
 	 *
 	 * @access 'protected'
 	 * @return object
	 */

	protected static function validator($params=array())
	{
		if(empty($params))
		{
			$params = self::$params;
		}

		if(empty(self::$validator))
		{
			self::$validator = new SpryApiValidator($params);
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
	 */

	protected static function stop($response_code=0, $data=null, $messages=[])
	{
		if(!empty($messages) && (is_string($messages) || is_numeric($messages)))
		{
			$messages = [$messages];
		}

		if(!empty(self::$config->hooks->stop) && is_array(self::$config->hooks->stop))
		{
			$params = [
				'response_code' => $response_code,
				'data' => $data,
				'messages' => $messages
			];

			foreach (self::$config->hooks->stop as $filter)
			{
				self::get_response(self::get_controller($filter), $params);
			}
		}

		$response = self::build_response($response_code, $data, $messages);

		self::send_response($response);
	}



	/**
	 * Sets the Auth object.
 	 *
 	 * @access 'protected'
 	 * @return object
	 */

	protected static function set_auth($object)
	{
		self::$auth = $object;
	}



	/**
	 * Returns the Auth object.
 	 *
 	 * @access 'protected'
 	 * @return object
	 */

	protected static function auth()
	{
		return self::$auth;
	}



	/**
	 * Returns the Config Parameters from the Singleton Class.
 	 *
 	 * @access 'protected'
 	 * @return object
	 */

	protected static function config()
	{
		return self::$config;
	}



	/**
	 * Return a formatted alphnumeric safe version of the string.
	 *
 	 * @param string $string
 	 *
 	 * @access 'protected'
 	 * @return string
	 */

	protected static function sanitize($string)
	{
		return preg_replace("/\W/g", '', str_replace([' ', '-'], '_', strtolower($string)));
	}



	/**
	 * Gets the Data sent in the API Call and converts it to Parameters.
	 * Then returns the converted Parameters as array.
	 * Throughs stop() on failure.
 	 *
 	 * @access 'private'
 	 * @return array
	 */

	private static function fetch_params()
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

		if(!empty($data))
		{
			if(!empty(self::$config->hooks->fetch_params) && is_array(self::$config->hooks->fetch_params))
			{
				foreach (self::$config->hooks->fetch_params as $filter)
				{
					$data = self::get_response(self::get_controller($filter), $data);
				}
			}
		}

		if(!empty($data) && !is_array($data))
		{
			self::stop(5014); // Returned Data is not in JSON format
		}

		if(!empty($data) && is_array($data))
		{
			return $data;
		}

		self::stop(5010); // No Parameters Found
	}




	/**
	 * Gets the Data sent in the API Call and converts it to Parameters.
	 * Then returns the converted Parameters as array.
	 * Throughs stop() on failure.
 	 *
 	 * @access 'protected'
 	 * @return array
	 */

	protected static function params($param='')
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
	 * Sets the Param Data
 	 *
 	 * @access 'protected'
 	 * @return bool
	 */

	protected static function set_params($params=[])
	{
		if(empty($params) || !is_array($params))
		{
			return false;
		}

		self::$params = array_merge(self::$params, $params);

		return true;
	}



	/**
	 * Gets the URL Path of the current API Call.
 	 *
 	 * @access 'protected'
 	 * @return string
	 */

	protected static function get_path()
	{
		$path = explode('?', strtolower($_SERVER['REQUEST_URI']), 2);
		$path = self::clean_path($path[0]);

		if(!empty(self::$config->hooks->get_path) && is_array(self::$config->hooks->get_path))
		{
			foreach (self::$config->hooks->get_path as $filter)
			{
				$path = self::get_response(self::get_controller($filter), $path);
			}
		}

		return $path;
	}



	/**
	 * Cleans the Path given to a specified format.
 	 *
 	 * @access 'private'
 	 * @return string
	 */

	private static function clean_path($path)
	{
		if(substr($path, -1) === '/')
		{
			return trim($path);
		}
		return trim($path).'/';
	}



	/**
	 * Returns the Controller Object and Method by name.
	 * Throughs stop() on failure.
	 *
	 * @param string $controller_name
 	 *
 	 * @access 'private'
 	 * @return array
	 */

	private static function get_controller($controller_name='')
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
				self::stop(5013, null, $controller_name); // Method Not Found
			}
		}

		self::stop(5012, null, $controller_name); // Controller Not Found
	}



	/**
	 * Creates a one way Hash value used for Passwords and other authentication.
	 *
 	 * @param string $value
 	 *
 	 * @access 'private'
 	 * @return string
	 */

	protected static function hash($value)
	{
		$salt = '';

		if(!empty(self::$config->salt))
		{
			$salt = self::$config->salt;
		}

		return md5(serialize($value).$salt);
	}



	/**
	 * Returns the Root Directory of the API.
 	 *
 	 * @access 'protected'
 	 * @return object
	 */

	protected static function dir()
	{
		return dirname(__FILE__);
	}



	/**
	 * Return just the body of the request is successfull.
	 *
 	 * @param string $result
 	 *
 	 * @access 'protected'
 	 * @return mixed
	 */

	protected static function get_body($result)
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
	 */

	protected static function results($response_code=0, $data=null, $messages=[])
	{
		$response_code = strval($response_code);

		if(strlen($response_code) < 3)
		{
			$response_code = '0'.$response_code;
		}

		if(strlen($response_code) > 3)
		{
			return self::build_response($response_code, $data, $messages);
		}

		if(empty($data) && $data !== null && $data !== 0 && (!self::$db || (self::$db && !self::$db->has_error())))
		{
			return self::build_response('4' . $response_code, $data, $messages);
		}

		if(!empty($data) || $data === 0)
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
	 */

	private static function build_response($response_code=0, $data=null, $messages=[])
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

		if(!empty(self::$config->hooks->build_response) && is_array(self::$config->hooks->build_response))
		{
			foreach (self::$config->hooks->build_response as $filter)
			{
				$response = self::get_response(self::get_controller($filter), $response);
			}
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
	 */

	private static function get_response($controller=array(), $params=null)
	{
		if(!is_callable(array($controller['obj'], $controller['method'])))
		{
			self::stop(5015, null, $controller['method']);
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
 	 * @access 'protected'
 	 * @return void
	 */

	protected static function send_response($response=array())
	{
		if(empty($response['response']) || empty($response['response_code']))
		{
			$response = self::build_response('', $response);
		}

		if(!empty(self::$config->hooks->send_response) && is_array(self::$config->hooks->send_response))
		{
			foreach (self::$config->hooks->send_response as $filter)
			{
				$response = self::get_response(self::get_controller($filter), $response);
			}
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
	 */

	private static function send_output($output=array())
	{
		$headers = [
			'Access-Control-Allow-Origin: *',
			'Access-Control-Allow-Methods: GET, POST, OPTIONS',
			'Access-Control-Allow-Headers: X-Requested-With, content-type'
		];

		$output = ['headers' => $headers, 'body' => json_encode($output)];

		if(!empty(self::$config->hooks->send_output) && is_array(self::$config->hooks->send_output))
		{
			foreach (self::$config->hooks->send_output as $filter)
			{
				$output = self::get_response(self::get_controller($filter), $output);
			}
		}

		if(!empty($output['headers']))
		{
			foreach ($output['headers'] as $header)
			{
				header($header);
			}
		}

		if(!empty($output['body']))
		{
			echo $output['body'];
		}

		exit;
	}

}
