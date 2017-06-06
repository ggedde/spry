<?php

namespace Spry\SpryComponent;

use Spry\Spry as Spry;

class Auth
{
	private $auth_fields = [
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

	public function get()
	{
		if(!empty(Spry::auth()->account_id) && !empty(Spry::auth()->user_id) && !empty(Spry::auth()->user_access_key))
		{
			$request = [
				'account_id' => Spry::auth()->account_id,
				'user_id' => Spry::auth()->user_id,
				'access_key' => Spry::auth()->user_access_key,
			];

			return Spry::results(200, $request);
		}

		sleep(5); // Reduce Hack attempts
		return Spry::results(200);
	}





	public function check()
	{
		// Skip this Check if request is by username and password
		if(Spry::get_path() === '/auth/get/')
		{
			$username = Spry::validator()->required()->minLength(1)->validate('username');
			$password = Spry::validator()->required()->minLength(1)->validate('password');

			sleep(1); // Reduce Hack attempts

			$where = [
				'AND' => [
					'users.username' => $username,
					'users.password' => Spry::hash($password),
					'accounts.status' => 'active'
				]
			];
		}
		else
		{
			// Run Auth Check
			$access_key = Spry::validator()->required()->minLength(1)->validate('access_key');

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

		$request = Spry::db()->get('accounts', $join, $this->auth_fields, $where);

		if(!empty($request['account_id']))
		{
			$auth = (object) $request;
			if($auth->user_permissions !== '*')
			{
				$auth->user_permissions = json_decode($auth->user_permissions, true);
			}
			Spry::set_auth($auth);
			return true;
		}

		sleep(5); // Reduce Hack attempts
		Spry::stop(5201);
	}



	public function get_permissions()
	{
		$permissions = array_keys(Spry::config()->routes);

		return Spry::results(205, $permissions);
	}



	public function has_permission($path='')
	{
		if(!$path)
		{
			$path = Spry::get_path();
		}

		if(!empty(Spry::auth()->user_permissions))
		{
			$permissions = Spry::auth()->user_permissions;
		}

		if(empty($permissions) || (!is_array($permissions) && $permissions !== '*') || (is_array($permissions) && !in_array($path, $permissions)))
		{
			return false;
		}

		return true;
	}



	public function check_permissions()
	{
		$path = Spry::get_path();

		// Skip this Check if request is by username and password
		if($path === '/auth/get/')
		{
			return;
		}

		if(!$this->has_permission($path))
		{
			sleep(2); // Reduce Hack attempts
			Spry::stop(5204);
		}
	}

}
