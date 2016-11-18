# SpryAPI
A lightweight PHP API Framework

Current Release: 1.1.0

REQUIRES:
* PHP 5.4

Included Extensions:
* Medoo Database Class - http://medoo.in/
* Field Validation Class - https://github.com/blackbelt/php-validation


## How to Use
index.php
```
require 'v1.0.0/app.php';
API::run();
```


## Configuration
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
	4200 => ['en' => 'Unknown: Unkown response for Authentication'],
	5200 => ['en' => 'Error: Username and Password are Incorrect'],
  ...
];

// Auth Filters
// $config->pre_auth_filter = 'YOUR_CONTROLLER::pre_auth_filter';
// $config->post_auth_filter = 'YOUR_CONTROLLER::post_auth_filter';
```

## Response Codes / Multiple Lingual
Response Codes are configured in "config.php".
Here is the format:
5200 = [5][200]

The first digit Represents the response type 5=Error, 4=Unknown, 2=Success
The next digits represents the Controller Methods.  You Can do whatever you want with these.

When using the code you will only need to place '200' in the 'parent::results()' method.  The Method will handle the rest for you.

Example Usage:
```
	$response = parent::db()->get('accounts', '*');
	return parent::results(200, $response);
```
If $response is successfull then the method will return 2200. If $response is null or fatal error, then it will return 4200. If $response it not empty, but contains response['error'] the the method will return 5200.

### Multlingual Response Codes
The Response Codes are set up to be multi-lingual.  For this to work your App needs to send the "lang" param to your API.  The default is "en".  You can still pass custom messages in your API, but custom messages don't support Multi-Lingual.  That is why it is best for you to use the Response Codes as intended.

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
## Adding Controller Methods to Routes
config.php
```
$config->routes = [
	'/auth/get' => 'AUTH::get',
	'/account/get' => 'ACCOUNT::get',
	'/your_controller/get_all' => 'YOUR_CONTROLLER::get_all',
];
```

## Creating Auth Filters

***! It is Recommended that you use Network (Hardware/Firewall) to protect your App first.  Only use App Filtering if required and needed for billing/code support. Network filtering is faster and requires less resources on your app.***


config.php
```
$config->pre_auth_filter = 'YOUR_CONTROLLER::pre_auth_filter';
$config->post_auth_filter = 'YOUR_CONTROLLER::post_auth_filter';
```
Then Create a controller and add the methods.

Note that "pre_auth_filter" will run prior to user authentication and prior to any database connections.

This is the fastest method and blocking requests here will help prevent load on your server, but it does not have all the data that you might require.


"post_auth_filter" has access to the user that authenticated with parent::account_id() and has access to the database with parent::db()

Example:
```
class YOUR_CONTROLLER extends API {
	public function pre_auth_filter()
	{
		$path = parent::get_path();
		$params = parent::params();
		$ip = $_SERVER['REMOTE_ADDR'];

		// DO something
		parent::stop_error(9999, null, 'Error: Bla Bla Bla');
	}

	public function post_auth_filter()
	{
		$path = parent::get_path();
		$params = parent::params();
		$account_id = parent::account_id();
		$ip = $_SERVER['REMOTE_ADDR'];

		// Check DB for Limits
		$limits = parent::db()->select('limit_table', '*', ['acount_id' => $account_id]);


		if(count($limits > 500))
		{
			// DO something
			parent::stop_error(9999, null, 'Error: You Have Reached your Limit');
		}
	}
}
```

## Caching
Currently there is no built in support for caching.

However, you can use the "pre_auth_filter" and "post_auth_filter" to run your own caching methods.  You can use  parent::get_path(), parent::params(), parent::account_id() to determine how to cache or retreive cache data.

### App Rendering support
On Successfull responses the API will hash the body parameter and return [body_hash].  You can use this to determine if the data has changed since the last request.  If the hashes don't match then re-render your apps view.


## Managing Multiple Versions

When your API requires updates that might break the way current users are using then you would want to create another endpoint.  The easiest way is to just copy the v1.0.0 folder to v2.0.0, etc.  Then make a new endpoint and point to that folder.

index2.php
```
require 'v2.0.0/app.php';
API::run();
```

## Adding Extensions
To add your own extension just upload the Class file in /extensions/ folder.  The file needs to be a class where the class name matchs the file.php name.  Then just call the class and the autoloader will do the rest.

/extensions/my_extension.php
```
class MY_EXTENSION {
	
}
```
in your controller
```
$my = new MY_EXTENSION();
```
Or if Singleton
```
MY_EXTENSION::some_method();
```



## Changelog

### 1.1.0 (Nov 18 2016)
* Added ability to get Nested params by using the "." separator in the parent::params() method
