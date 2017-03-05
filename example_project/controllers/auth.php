<?php

class AUTH extends SpryApi
{

	private static $auth_fields = [
		'accounts.id(account_id)',
		'users.id(user_id)',
		'users.permissions(user_permissions)',
		'users.access_key(user_access_key)'
	];

	/**
	 * Returns the Account ID and Access_key
	 *
 	 * @param string $username
 	 * @param string $password
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function get()
	{
		if(!empty(parent::auth()->account_id) && !empty(parent::auth()->user_id) && !empty(parent::auth()->user_access_key))
		{
			$request = [
				'account_id' => parent::auth()->account_id,
				'user_id' => parent::auth()->user_id,
				'access_key' => parent::auth()->user_access_key,
			];

			return parent::results(200, $request);
		}

		sleep(5); // Reduce Hack attempts
		return parent::results(200);
	}





	public static function check()
	{
		// Skip this Check if request is by username and password
		if(parent::get_path() === '/auth/get/')
		{
			$username = parent::validator()->required()->minLength(1)->validate('username');
			$password = parent::validator()->required()->minLength(1)->validate('password');

			sleep(1); // Reduce Hack attempts

			$where = [
				'AND' => [
					'users.username' => $username,
					'users.password' => parent::hash($password),
					'accounts.status' => 'active'
				]
			];
		}
		else
		{
			// Run Auth Check
			$access_key = parent::validator()->required()->minLength(1)->validate('access_key');

			$where = [
				'AND' => [
					'users.access_key' => $access_key,
					'accounts.status' => 'active'
				]
			];
		}

		$join = [
			"[>]users" => ["id" => "users.account_id"]
		];

		$request = parent::db()->get('accounts', $join, self::$auth_fields, $where);

		if(!empty($request['account_id']))
		{
			$auth = (object) $request;
			if($auth->user_permissions !== '*')
			{
				$auth->user_permissions = json_decode($auth->user_permissions, true);
			}
			parent::set_auth($auth);
			return true;
		}

		sleep(5); // Reduce Hack attempts
		self::stop_error(5201);
	}



	public static function get_permissions()
	{
		$permissions = array_keys(parent::config()->routes);

		return parent::results(205, $permissions);
	}



	public static function has_permission($path='')
	{
		if(!$path)
		{
			$path = parent::get_path();
		}

		if(!empty(parent::auth()->user_permissions))
		{
			$permissions = parent::auth()->user_permissions;
		}

		if(empty($permissions) || (!is_array($permissions) && $permissions !== '*') || (is_array($permissions) && !in_array($path, $permissions)))
		{
			return false;
		}

		return true;
	}



	public static function check_permissions()
	{
		$path = parent::get_path();

		// Skip this Check if request is by username and password
		if($path === '/auth/get/')
		{
			return;
		}

		if(!self::has_permission($path))
		{
			sleep(2); // Reduce Hack attempts
			parent::stop_error(5204);
		}
	}

}
