<?php

class EXAMPLE extends API
{
	private static $table = 'examples_table';
	

	/**
	 * Inserts an Item
	 *
 	 * @param string $access_key
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function insert()
	{
		// Required Fields
		$name = parent::validator()->required()->minLength(1)->validate('name');

		$data = [
			'account_id' => parent::auth()->account_id,
			'name' => $name
		];

		return parent::results(302, parent::db()->insert(self::$table, $data));
	}

	/**
	 * Updates an Item
	 *
 	 * @param string $access_key
 	 * @param int $id
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function update()
	{
		// Required Fields
		$id = parent::validator()->required()->integer()->min(1)->validate('id');
		$name = parent::validator()->required()->minLength(1)->validate('name');

		$data = [
			'name' => $name
		];

		$where = [
			'AND' => [
				'account_id' => parent::auth()->account_id,
				'id' => $id
			]
		];

		return parent::results(303, parent::db()->update(self::$table, $data, $where));

	}



	/**
	 * Returns a Single Item by Account
	 *
 	 * @param string $access_key
 	 * @param int $id
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function get()
	{
		// Required Fields
		$id = parent::validator()->required()->integer()->min(1)->validate('id');

		$where = [
			'AND' => [
				'account_id' => parent::auth()->account_id,
				'id' => $id
			]
		];

		return parent::results(300, parent::db()->get(self::$table, '*', $where));
	}



	/**
	 * Returns all Items by Account
	 *
 	 * @param string $access_key
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function get_all()
	{
		$where = [
			'AND' => [
				'account_id' => parent::auth()->account_id,
			],
			'ORDER' => 'id DESC',
			'GROUP' => 'id'
		];

		return parent::results(301, parent::db()->select(self::$table, '*', $where));
	}



	/**
	 * Deletes an Item From Account by id
	 *
 	 * @param int $id
 	 * @param string $access_key
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public static function delete()
	{
		$id = parent::validator()->required()->integer()->min(1)->validate('id');

		$where = [
			'AND' => [
				'account_id' => parent::auth()->account_id,
				'id' => $id
			]
		];

		return parent::results(304, parent::db()->delete(self::$table, $where));
	}

}
