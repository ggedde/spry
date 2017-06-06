<?php

namespace Spry\SpryComponent;

use Spry\Spry as Spry;

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

		$response_code = 2050;

		$last_response_body = null;
		$last_response_body_id = null;

        $result = [];

        if($requested_test_name && !isset(Spry::config()->tests[$requested_test_name]))
        {
            $response_code = 5053;
        }

		foreach (Spry::config()->tests as $test_name => $test)
		{
            // Skip this test if a Test Name was Requested and does not match this Test.
            if($requested_test_name && $requested_test_name != $test_name)
            {
                continue;
            }

			$result[$test_name] = [
                'status' => 'Passed',
                'expect' => [],
                'result' => [],
            ];

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

			$response = self::get_api_response(json_encode($test['params']), Spry::config()->endpoint.$test['route']);
			$response = json_decode($response, true);

            $result[$test_name]['full_response'] = $response;

			if(!empty($test['expect']) && is_array($test['expect']))
			{
				$result[$test_name]['result'] = [];

                if(empty($test['expect']))
                {
                    $result[$test_name]['status'] = 'Failed';
                    $response_code = 5050;
                }
                else
                {
                    $result[$test_name]['expect'] = $test['expect'];

    				foreach ($test['expect'] as $expect_key => $expect)
    				{
    					$result[$test_name]['result'][$expect_key] = $response[$expect_key];

    					if(empty($response[$expect_key]) || $response[$expect_key] !== $expect)
    					{
    						$result[$test_name]['status'] = 'Failed';
    						$response_code = 5050;
    					}
    				}
                }
			}

			$last_response_body = (!empty($response['body']) ? $response['body'] : null);
			$last_response_body_id = (!empty($response['body']['id']) ? $response['body']['id'] : null);
		}

		return Spry::results($response_code, $result);
	}
}
