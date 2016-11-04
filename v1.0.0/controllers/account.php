<?php

class ACCOUNT extends API
{
	private static $table = 'accounts';
	
	
	/**
	 * Returns the Account by Access_key
	 *
 	 * @param string $username
 	 * @param string $password
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function get()
	{
		$where = [
			'AND' => [
				'id' => parent::account_id(),
				'status' => 'active'
			]
		];

		$fields = [
			'id',
			'type',
			'username',
			'email',
			'server_ip',
			'created_on'
		];

		return parent::results(400, parent::db()->get(self::$table, $fields, $where));
	}

}
