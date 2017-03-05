<?php

/**
 *
 *  Generic Log Class to catch API Logs and PHP Error Logs
 *  Version 1.0.1
 *
 */

class LOG extends SpryApi
{
	/**
	 * Log a generic Message
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	private static function write_log($msg)
	{
		if(parent::config()->api_log_file)
		{
			date_default_timezone_set('America/Los_Angeles');
			$msg = "\n".date('Y-m-d H:i:s').' '.$_SERVER['REMOTE_ADDR'].' - '.$msg;
			file_put_contents(parent::config()->api_log_file, $msg, FILE_APPEND);
		}
	}


	/**
	 * Log a generic Message
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function message($msg)
	{
		self::write_log('GitShack: '.$msg);
	}


	/**
	 * Log a generic Warning
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function warning($msg)
	{
		self::write_log('GitShack WARNING: '.$msg);
	}


	/**
	 * Log a generic Error
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function error($msg)
	{
		self::write_log('GitShack ERROR: '.$msg);
	}


	/**
	 * Log a Hard Stop Error
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function stop_error_filter($params)
	{
		$messages = (!empty($params['messages']) && is_array($params['messages']) ? implode(', ', $params['messages']) : '');
		$msg = 'Response Code ('.$params['response_code'].') - '.$messages;

		self::write_log('GitShack STOP ERROR: '.$msg);
	}



	/**
	 * Log a Response
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function build_response_filter($response)
	{
		$messages = (!empty($params['messages']) && is_array($params['messages']) ? implode(', ', $params['messages']) : '');
		$msg = 'Response Code ('.$response['response_code'].') - '.$messages;

		self::write_log('GitShack Build Response: '.$msg);

		return $response;
	}



	/**
	 * Log a Initiating Request
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function initial_request()
	{
		$secure = ['password', 'pass', 'access_key', 'secret'];
		$params = parent::params();

		foreach ($params as $param_key => $param_value)
		{
			if(in_array($param_key, $secure))
			{
				$params[$param_key] = 'xxxxxxxx';
			}
		}

		self::write_log("GitShack Initial Request: - - - - - - - - - - - - - - - - - - \nPath: ".parent::get_path()."\nParams:\n".print_r($params, true));
	}



	/**
	 * Log a User Request
 	 *
 	 * @access 'public'
 	 * @return void
	 */

	public static function user_request()
	{
		$data = [
			'account_id' => parent::auth()->account_id,
			'user_id' => parent::auth()->user_id,
			'request' => parent::get_path(),
			'permitted' => (AUTH::has_permission() ? 1 : 0)
		];

		parent::db()->insert('logs', $data);
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

	protected static function php_log_handler($errno, $errstr, $errfile, $errline)
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

			file_put_contents(parent::config()->php_log_file, $data, FILE_APPEND);
		}
	}



	/**
	 * Checks the API on Shutdown for Fatal Errors.
 	 *
 	 * @access 'protected'
 	 * @return void
 	 * @final
	 */

	protected static function php_shut_down_function()
	{
	    $error = error_get_last();
	    if ($error['type'] === E_ERROR)
	    {
	        self::php_log_handler($error['type'], $error['message'], $error['file'], $error['line']);
	    }
	}



	/**
	 * Sets up the PHP Log Handlers.
 	 *
 	 * @access 'public'
 	 * @return void
 	 * @final
	 */

	public static function setup_php_logs()
	{
		if(parent::config()->php_log_file)
		{
	    	set_error_handler(array(__CLASS__, 'php_log_handler'));
	    	register_shutdown_function(array(__CLASS__, 'php_shut_down_function'));
	    }
	}


}
