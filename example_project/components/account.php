<?php

namespace Spry\SpryComponent;

use Spry\Spry as Spry;

class Account
{
	private $table = 'accounts';

	/**
	 * Returns the Account
	 *
 	 * @param string $username
 	 * @param string $password
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public function get()
	{
		$where = [
			'AND' => [
				'id' => Spry::auth()->account_id,
				'status' => 'active'
			]
		];

		return Spry::results(400, Spry::db()->get($this->table, '*', $where));
	}

}
