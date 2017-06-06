<?php

namespace Spry\SpryComponent;

use Spry\Spry as Spry;

class Example
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
		$name = Spry::validator()->required()->minLength(1)->validate('name');

		$data = [
			'account_id' => Spry::auth()->account_id,
			'name' => $name
		];

		return Spry::results(302, Spry::db()->insert($this->table, $data));
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
		$id = Spry::validator()->required()->integer()->min(1)->validate('id');
		$name = Spry::validator()->required()->minLength(1)->validate('name');

		$data = [
			'name' => $name
		];

		$where = [
			'AND' => [
				'account_id' => Spry::auth()->account_id,
				'id' => $id
			]
		];

		return Spry::results(303, Spry::db()->update($this->table, $data, $where));

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
		$id = Spry::validator()->required()->integer()->min(1)->validate('id');

		$where = [
			'AND' => [
				'account_id' => Spry::auth()->account_id,
				'id' => $id
			]
		];

		return Spry::results(300, Spry::db()->get($this->table, '*', $where));
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
				'account_id' => Spry::auth()->account_id,
			],
			'ORDER' => 'id DESC',
			'GROUP' => 'id'
		];

		return Spry::results(301, Spry::db()->select($this->table, '*', $where));
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
		$id = Spry::validator()->required()->integer()->min(1)->validate('id');

		$where = [
			'AND' => [
				'account_id' => Spry::auth()->account_id,
				'id' => $id
			]
		];

		return Spry::results(304, Spry::db()->delete($this->table, $where));
	}

}
