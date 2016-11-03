<?php

class EXAMPLE extends API
{
	private $table = 'examples_table';

	/**
	 * Inserts an Item
	 *
 	 * @param string $access_key
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public function insert()
	{
		// Required Fields
		$name = parent::validator()->required()->minLength(1)->validate('name');

		$data = [
			'account_id' => parent::account_id(),
			'name' => $name
		];

		return parent::results(302, parent::db()->insert($this->table, $data));
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

	public function update()
	{
		// Required Fields
		$id = parent::validator()->required()->integer()->min(1)->validate('id');
		$name = parent::validator()->required()->minLength(1)->validate('name');

		$data = [
			'name' => $name
		];

		$where = [
			'AND' => [
				'account_id' => parent::account_id(),
				'id' => $id
			]
		];

		return parent::results(303, parent::db()->update($this->table, $data, $where));

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

	public function get()
	{
		// Required Fields
		$id = parent::validator()->required()->integer()->min(1)->validate('id');

		$where = [
			'AND' => [
				'account_id' => parent::account_id(),
				'id' => $id
			]
		];

		return parent::results(300, parent::db()->get($this->table, '*', $where));
	}



	/**
	 * Returns all Items by Account
	 *
 	 * @param string $access_key
 	 *
 	 * @access 'public'
 	 * @return array
	 */

	public function get_all()
	{
		$where = [
			'AND' => [
				'account_id' => parent::account_id(),
			],
			'ORDER' => 'id DESC',
			'GROUP' => 'id'
		];

		return parent::results(301, parent::db()->select($this->table, '*', $where));
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

	public function delete()
	{
		$id = parent::validator()->required()->integer()->min(1)->validate('id');

		$where = [
			'AND' => [
				'account_id' => parent::account_id(),
				'id' => $id
			]
		];

		return parent::results(304, parent::db()->delete($this->table, $where));
	}

}