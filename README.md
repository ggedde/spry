# SpryAPI
Fast PHP API Framework

## How to Use
index.php
```
require 'v1.0.0/app.php';
API::run();
```
  
  
## How to Configure
config.php
```
// Salt for Security.
$config->salt = 'asdfghjklkjhgfdsasdfghjklpoiuytrewqazxcvbnm';

// Database
$config->db = [
	'database_type' => 'mysql',
	'database_name' => '',
	'server' => 'localhost',
	'username' => '',
	 ...
];

// Routes
$config->routes = [
	'/auth/get' => 'AUTH::get',
	'/account/get' => 'ACCOUNT::get',
];

// Response Codes and Messages.  Multi-Lingual support
$config->response_codes = [

	/* General */
	4000 => ['en' => 'No Results Found'],
	5100 => ['en' => 'Error: Field did not Validate.'],
	...

	/* Auth */
	2200 => ['en' => 'Authentication Passed Successfully'],
	5200 => ['en' => 'Error: Invalid Username and Password'],
	5201 => ['en' => 'Error: Account is Not Valid'],
  ...
];
```

## Creating Controllers
controllers/YOUR_CONTROLLER.php
```
class YOUR_CONTROLLER extends API
{
	public function get_all()
	{
		$where = [
			'AND' => [
				'account_id' => parent::account_id(),
			],
		];

		return parent::results(301, parent::db()->select('table_name_here', '*', $where));
	}
}
```
Then add to your Routes in "config.php"
```
$config->routes = [
	'/auth/get' => 'AUTH::get',
	'/account/get' => 'ACCOUNT::get',
	'/your_controller/get_all' => 'YOUR_CONTROLLER::get_all',
];
```
