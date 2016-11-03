<?php

class AUTH extends API {


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
		$username = parent::validator()->required()->minLength(1)->validate('username');
		$password = parent::validator()->required()->minLength(1)->validate('password');

		if(!empty($username) && !empty($password))
		{
			sleep(1); // Reduce Hack attempts

			$where = [
				'AND' => [
					'username' => $username,
					'password' => parent::hash($password),
					'status' => 'active'
				]
			];

			$response = parent::db()->get('accounts', ['id', 'access_key'], $where);

			if(!empty($response['id']))
			{
				return parent::results(200, $response);
			}
		}

		sleep(5); // Reduce Hack attempts

		return parent::results(200);
	}



	/**
	 * Returns the Account ID
	 *
 	 * @param string $access_key
 	 *
 	 * @access 'public'
 	 * @return int
	 */

	public function get_account_id()
	{
		if($access_key = parent::params('access_key'))
		{
			$where = [
				'AND' => [
					'access_key' => $access_key,
					'status' => 'active'
				]
			];

			$id = parent::db()->get('accounts', 'id', $where);

			if(!empty($id) && is_numeric($id))
			{
				return $id;
			}
		}

		sleep(5); // Reduce Hack attempts

		return 0;
	}

}