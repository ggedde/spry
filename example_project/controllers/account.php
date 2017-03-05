<?php

class ACCOUNT extends SpryApi
{
	static private $table = 'accounts';

	/**
	 * Returns the Account
	 *
 	 * @param string $username
 	 * @param string $password
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	static public function get()
	{
		$where = [
			'AND' => [
				'id' => parent::auth()->account_id,
				'status' => 'active'
			]
		];

		return parent::results(400, parent::db()->get(self::$table, '*', $where));
	}

}
