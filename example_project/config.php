<?php

// Salt for Security.  Change this to be Unique for each one of your API's.
// You should use a long and Strong key.
// DO NOT CHANGE IT ONCE YOU HAVE CREATED DATA.  Otherwise things like logins may no longer work.
$config->salt = '';

// Set PHP and API Log file Locations
// THESE SHOULD BE DISABLED IN PRODUCTION (OR AT LEAST SET SOMEWHERE IN A PRIVATE FOLDER)
$config->php_log_file = __DIR__.'/php.log';
$config->api_log_file = __DIR__.'/api.log';

// Set PHP Error Types
ini_set('error_reporting', E_ALL);

$config->endpoint = 'http://localhost';
$config->components_dir = __DIR__.'/components';


// WebTools
$config->webtools_enabled = false;
$config->webtools_endpoint = '/webtools';  	// Make sure this is unique and does not clash with your controllers
$config->webtools_username = '';			// Username is required
$config->webtools_password = '';			// Password is required
$config->webtools_allowed_ips = [
	'127.0.0.1'
];

// Database
$config->db = [
	'database_type' => 'mysql',
	'database_name' => '',
	'server' => 'localhost',
	'username' => '',
	'password' => '',
	'charset' => 'utf8',
	'port' => 3306,
	'prefix' => 'api_x_', // Should change this to be someting Uniue
	'migrate_destructive' => false,
	'migrate_schema' => [
		'tables' => [
			'users' => [
				'columns' => [
					'name' => [
						'type' => 'string'
					],
					'email' => [
						'type' => 'string'
					],
				]
			]
		]
	]
];

// Routes
$config->routes = [
	'/auth/get' => 'Auth::get',
	'/account/get' => 'Account::get',

	// '/example/get' => 'Example::get',
	// '/example/get_all' => 'Example::get_all',
	// '/example/insert' => 'Example::insert',
	// '/example/update' => 'Example::update',
	// '/example/delete' => 'Example::delete'
];

$config->response_codes = [

	/* Auth */
	2200 => ['en' => 'Authentication Passed Successfully'],
	5200 => ['en' => 'Error: Invalid Username and Password'],
	5201 => ['en' => 'Error: Account is Not Valid'],

	2201 => ['en' => 'Successfully'],

	2202 => ['en' => 'Successfully Created Request Token'],
	5202 => ['en' => 'Error: Creating Request Token'],

	2203 => ['en' => 'Successfully Granted Access'],
	5203 => ['en' => 'Error: Creating Access Token'],

	/* Example */
	// 2300 => ['en' => 'Successfully Retrieved Example'],
	// 4300 => ['en' => 'No Example with that ID Found'],
	// 5300 => ['en' => 'Error: Retrieving Example'],

	// 2301 => ['en' => 'Successfully Retrieved Examples'],
	// 4301 => ['en' => 'No Results Found'],
	// 5301 => ['en' => 'Error: Retrieving Examples'],

	// 2302 => ['en' => 'Successfully Created Example'],
	// 5302 => ['en' => 'Error: Creating Example'],

	// 2303 => ['en' => 'Successfully Updated Example'],
	// 4303 => ['en' => 'No Example with that ID Found'],
	// 5303 => ['en' => 'Error: Updating Example'],

	// 2304 => ['en' => 'Successfully Deleted Example'],
	// 5304 => ['en' => 'Error: Deleting Example'],

	/* Accounts */
	2400 => ['en' => 'Successfully Retrieved Account'],
	5400 => ['en' => 'Error: Retrieving Account'],

];

// Tests
$config->tests = [
	'connection' => [
		'title' => 'Connection Test with Empty Parameters',
		'route' => '/testconnection',
		'params' => [],
		'expect' => [
			'response_code' => 5010,
		]
	],
	'test1' => [
		'route' => '/account/get',
		'params' => [
			'access_key' => 'xxxxxxxxxxxxxxxxxxxxxxxx'
		],
		'expect' => [
			'response' => 'success',
		]
	],
];

// Filters
$config->hooks->configure = ['SpryLog::setup_php_logs'];
$config->hooks->params = ['SpryLog::initial_request'];
// $config->hooks->database =  = ['AUTH::check'];
// $config->hooks->routes = ['SpryLog::user_request'];
$config->hooks->stop = ['SpryLog::stop_filter'];
$config->hooks->build_response = ['SpryLog::build_response_filter']; // Filters must return the $response
// $config->hooks->send_response = []; // Filters must return the $response
// $config->hooks->get_path = [];  // Filters must return the $path
// $config->hooks->get_route = [];  // Filters must return the $route
// $config->hooks->fetch_params = [];  // Filters must return the $params
// $config->hooks->send_output = [];  // Filters must return the $output
