<?php

namespace SpryApi\SpryComponent;

use SpryApi\Spry as Spry;

class SpryTools {

    public static function get_api_response($request='', $url='')
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

    public static function get_hash($value='')
    {
        return Spry::hash($value);
    }

    public static function db_migrate($args=[])
	{
		$logs = [];

        if(empty(Spry::config()))
        {
            return Spry::results(5001, null);
        }

		if(!empty(Spry::config()->db))
		{
			$db = new SpryDB(Spry::config()->db);
			$logs = $db->migrate($args);
		}

		return Spry::results(30, $logs);
	}

    public static function test($requested_test_name='')
	{
		if(empty(Spry::config()->tests))
		{
			Spry::stop(5052);
		}

		$result = ['All Tests' => 'Passed'];
		$response_code = 2050;

		$last_response_body = null;
		$last_response_body_id = null;

		foreach (Spry::config()->tests as $test_name => $test)
		{
            // Skip this test if a Test Name was Requested and does not match this Test.
            if($requested_test_name && $requested_test_name != $test_name)
            {
                continue;
            }

			$result[$test_name] = ['response' => 'Passed'];

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

			$response = self::get_api_response(json_encode($test['params']), "http://".$_SERVER['HTTP_HOST'].$test['route']);

			$response = json_decode($response, true);

			$result[$test_name]['response_code'] = (!empty($response['response_code']) ? $response['response_code'] : '');
			$result[$test_name]['messages'] = (!empty($response['messages']) ? $response['messages'] : '');

			if(!empty($test['match']) && is_array($test['match']))
			{
				$result[$test_name]['response_match'] = [];

				foreach ($test['match'] as $match_key => $match)
				{
					$result[$test_name]['response_match'][$match_key] = $response[$match_key];

					if(empty($response[$match_key]) || $response[$match_key] !== $match)
					{
						$result[$test_name]['response'] = 'Failed';
						$result['All Tests'] = 'Failed';
						$response_code = 5050;
					}
				}
			}

			$last_response_body = (!empty($response['body']) ? $response['body'] : null);
			$last_response_body_id = (!empty($response['body']['id']) ? $response['body']['id'] : null);
		}

		return Spry::results($response_code, $result);
	}
}
