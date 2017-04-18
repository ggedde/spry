<?php

namespace SpryApi\SpryComponent;

use SpryApi\Spry as Spry;

/**
 *
 *  Generic Log Class to catch API Logs and PHP Error Logs
 *  Version 1.0.1
 *
 */

class SpryLog
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
		if(Spry::config()->api_log_file)
		{
			date_default_timezone_set('America/Los_Angeles');
			$msg = "\n".date('Y-m-d H:i:s').' '.$_SERVER['REMOTE_ADDR'].' - '.$msg;
			file_put_contents(Spry::config()->api_log_file, $msg, FILE_APPEND);
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
		self::write_log('Spry: '.$msg);
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
		self::write_log('Spry WARNING: '.$msg);
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
		self::write_log('Spry ERROR: '.$msg);
	}


	/**
	 * Log a Hard Stop Error
	 *
 	 * @param string $msg
 	 *
 	 * @access 'public'
 	 * @return bool
	 */

	public static function stop_filter($params)
	{
		$messages = (!empty($params['messages']) && is_array($params['messages']) ? implode(', ', $params['messages']) : '');
		$msg = 'Response Code ('.$params['response_code'].') - '.$messages;

		self::write_log('Spry STOPPED: '.$msg);
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

		self::write_log('Spry Build Response: '.$msg);

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
		$secure = [
			'password',
			'pass',
			'access_key',
			'key',
			'secret'
		];
		$params = Spry::params();

		foreach ($params as $param_key => $param_value)
		{
			if(in_array(strtolower($param_key), $secure))
			{
				$params[$param_key] = 'xxxxxx...';
			}
		}

		self::write_log("Spry Initial Request: - - - - - - - - - - - - - - - - - - \nPath: ".Spry::get_path()."\nParams:\n".print_r($params, true));
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
			'account_id' => Spry::auth()->account_id,
			'user_id' => Spry::auth()->user_id,
			'request' => Spry::get_path(),
			'permitted' => (Spry::auth()->has_permission() ? 1 : 0)
		];

		Spry::db()->insert('logs', $data);
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

	public static function php_log_handler($errno, $errstr, $errfile, $errline)
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
			    case E_USER_ERROR:
			    case E_CORE_ERROR:
			    case E_COMPILE_ERROR:
			    case E_RECOVERABLE_ERROR:
			        $errstr = 'PHP Fatal Error: '.$errstr;
			        break;

			    case 'SQL Error':
			        $errstr = 'SQL Error: '.str_replace('[SQL Error] ', '', $errstr);
			        break;

			    case E_WARNING:
			    case E_USER_WARNING:
			    case E_CORE_WARNING:
			    case E_COMPILE_WARNING:
			        $errstr = 'PHP Warning: '.$errstr;
			        break;

			    case E_NOTICE:
			    case E_USER_NOTICE:
			    case '8':
			        $errstr = 'PHP Notice: '.$errstr;
			        break;

			    case E_PARSE:
			        $errstr = 'PHP Parse Error: '.$errstr;
			        break;

			    case E_STRICT:
			        $errstr = 'PHP Strict: '.$errstr;
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
			file_put_contents(Spry::config()->php_log_file, $data, FILE_APPEND);
		}
	}



	/**
	 * Checks the API on Shutdown for Fatal Errors.
 	 *
 	 * @access 'public'
 	 * @return void
 	 * @final
	 */

	public static function php_shutdown_function()
	{
		$error = error_get_last();
	    if(!empty($error['type']) && !empty($error['message']))
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
		if(Spry::config()->php_log_file)
		{
	    	set_error_handler([__CLASS__, 'php_log_handler']);
	    	register_shutdown_function([__CLASS__, 'php_shutdown_function']);
	    }
	}


}
