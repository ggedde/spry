<?php

class SpryApiTools extends SpryApi {

    protected static function get_api_response($request='', $url='')
	{
		if(!empty($request))
		{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

			$response = curl_exec($ch);
			curl_close($ch);

			return $response;
		}
	}

    protected static function get_hash($value='')
    {
        return parent::hash($value);
    }

    protected static function db_migrate($args=[])
	{
		$logs = [];

        if(empty(parent::config()))
        {
            return parent::results(5001, null);
        }

		if(!empty(parent::config()->db))
		{
			$db = new SpryApiDB(parent::config()->db);
			$logs = $db->migrate($args);
		}

		return parent::results(30, $logs);
	}

    protected static function run_test($test='')
	{
		if(!empty(parent::config()->tests))
		{
			parent::stop(5052);
		}

		$result = ['All Tests' => 'Passed'];
		$response_code = 2050;

		$last_response_body = null;
		$last_response_body_id = null;

		foreach (parent::config()->tests as $route => $test)
		{
			$result[$route] = ['response' => 'Passed'];

			foreach ($test['params'] as $param_key => $param)
			{
				if($param === '{last_response_body}')
				{
					$test['params'][$param_key] = $last_response_body;
				}

				if($param === '{last_response_body_id}')
				{
					$test['params'][$param_key] = $last_response_body_id;
				}
			}

			$response = self::get_api_response(json_encode($test['params']), "http://".$_SERVER['HTTP_HOST'].$route);

			$response = json_decode($response, true);

			$result[$route]['response_code'] = (!empty($response['response_code']) ? $response['response_code'] : '');
			$result[$route]['messages'] = (!empty($response['messages']) ? $response['messages'] : '');

			if(!empty($test['match']) && is_array($test['match']))
			{
				$result[$route]['response_match'] = [];

				foreach ($test['match'] as $match_key => $match)
				{
					$result[$route]['response_match'][$match_key] = $response[$match_key];

					if(empty($response[$match_key]) || $response[$match_key] !== $match)
					{
						$result[$route]['response'] = 'Failed';
						$result['All Tests'] = 'Failed';
						$response_code = 5050;
					}
				}
			}

			$last_response_body = (!empty($response['body']) ? $response['body'] : null);
			$last_response_body_id = (!empty($response['body']['id']) ? $response['body']['id'] : null);
		}

		return parent::results($response_code, $result);
	}
}
