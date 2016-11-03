<?php

class ACCOUNT extends API
{
	/**
	 * Returns the Account by Access_key
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

		return parent::results(400, parent::db()->get('accounts', $fields, $where));
	}



	public function add_ssh_key()
	{
		$public_key = parent::validator()->required()->minLength(1)->validate('public_key');
		$description = parent::validator()->required()->minLength(1)->validate('description');

		$gitshack_request = [
			'action' => 'add_ssh_key',
			'public_key' => $public_key,
			'description' => $description
		];

		$account = $this->get();

		if(!empty($account['body']['server_ip']))
		{
			$result = GITSHACK::send_request($account['body']['server_ip'], $gitshack_request);

			if(!empty($result['response']) && $result['response'] === 'success')
			{
				$data = [
					'account_id' => parent::account_id(),
					'public_key' => $public_key,
					'description' => $description
				];

				if($db_result = parent::db()->insert('ssh_keys', $data))
				{
					return parent::results(420, $db_result);
				}
				else
				{
					// Remove Key as there is an issue
				}
			}

			if(!empty($result['response']) && $result['response'] === 'error' && !empty($result['message']))
			{
				return parent::results(420, null, $result['message']);
			}
		}

		return parent::results(420, null);
	}



	public function remove_ssh_key()
	{
		$key_id = parent::validator()->required()->minLength(1)->validate('key_id');

		$where = [
			'AND' => [
				'account_id' => parent::account_id(),
				'id' => $key_id
			]
		];

		$db_result = parent::db()->get('ssh_keys', '*', $where);

		if(empty($db_result['public_key']))
		{
			return parent::results(421, null, 'Key ID does not exist');
		}

		if(!empty($db_result['public_key']))
		{
			$account = $this->get();
			if(!empty($account['body']['server_ip']))
			{
				$gitshack_request = [
					'action' => 'remove_ssh_key',
					'public_key' => $db_result['public_key'],
				];
				$result = GITSHACK::send_request($account['body']['server_ip'], $gitshack_request);

				if(!empty($result['response']) && $result['response'] === 'success')
				{
					return parent::results(421, parent::db()->delete('ssh_keys', $where));
				}
			}
		}

		return parent::results(421, null);
	}


}