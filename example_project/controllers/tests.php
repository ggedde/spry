<?php

/**
 *
 *  Generic Tests Class to Test the Routes and Controllers
 *  Version 1.0.0
 *
 */

class Tests extends SpryApi
{

	/**
	 * Run the Tests and return its results
 	 *
 	 * @access 'public'
 	 * @return mixed
	 */

	public static function run()
	{
		if(empty(parent::config()->tests))
		{
			return parent::results(501, null);
		}

		$result = ['All Tests' => 'Passed'];
		$response_code = 2500;

		foreach (parent::config()->tests as $route => $test)
		{
			$result[$route] = ['response' => 'Passed'];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://".$_SERVER['HTTP_HOST'].$route);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['params']));

			$response = json_decode(curl_exec($ch), true);
			curl_close($ch);

			$result[$route]['response_code'] = (!empty($response['response_code']) ? $response['response_code'] : '');

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
						$response_code = 5502;
					}
				}
			}
		}

		parent::stop_error($response_code, $result);
	}

}
