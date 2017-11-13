# Spry
A lightweight PHP API Framework

BETA Release: 0.9.27

REQUIRES:
* PHP 5.4

Included Packages:
* Medoo Database Class - http://medoo.in/
* Field Validation Class - https://github.com/blackbelt/php-validation
* Background Processes - https://github.com/cocur/background-process


# Installation

The best way to install Spry and use it is through the CLI.
https://github.com/ggedde/spry-cli

Please reference the [Installation Process](https://github.com/ggedde/spry-cli#installation) on the CLI Page.


## Create a project through the CLI

	spry new [project_name]
	cd [project_name]

To Start the Spry server run

	spry up

Then open another termal and run some tests

	spry test

## Running Spry

	include dirname(__DIR__).'/vendor/autoload.php';
	include dirname(__DIR__).'/spry/init.php';
	
Change the directory structure accordingly.

When setting up spry through the CLI the above code will be automatically created for you in a "public" folder.

#### Contents of spry/init.php

    namespace Spry;
    
    define('SPRY_DIR', __DIR__);
    Spry::run(SPRY_DIR.'/config.php');

## Creating a Component
The easiest way is to use the CLI

    spry component NewComponent

OR

    spry c NewComponent
    
The above will create a new component file in your "components" folder from a spry example component template.

## Example Spry Component

    <?php
    namespace Spry\SpryComponent;

    use Spry\Spry;

    class Items
    {
        private static $table = 'items';

        public static function get()
        {
            // Validate and require 'id' from the Spry Params
            $id = Spry::validator()->required()->validate('id');
            
            // Set the DB WHERE Clause
            $where = ['id' => $id];
            
            // Retrieve the DB results
            $results = Spry::db()->get(self::$table, '*', $where);
            
            // Return the Response
            return Spry::response(000, $results);
        }
    }
    
	
## Folder Structure

	spry                   (Main Folder containing all Spry Files and components)
	 - components          (Folder containing all Component Files)
	   - Component1.php
	   - Component2.php
	   ...
	 - config.php          (Main Configuration File)
	 - init.php            (Main Loading File)
	 

## Configuration
spry/config.pnp

This file contains all the configuration for Spry. 

You can split up the file if you like and just include the separate parts into the config.php if you find that better.

An Example would be:

	- config.php
	- routes.php
	- log.php
	- tools.php
	- db-connect.php
	- db-schema.php
	- response-codes.php
	- tests.php

## Salt
This Variable contains the salt that is used with the "hash()" method to create hashes.

	$config->salt = '1234567890abcdefghijklmnopqrstuvwxyz';
	
\* WARNING:  changing this after data is stored to your database will result in errors.  It is best NOT to change this after any data has been stored.

## Logging
Spry comes with a built in provider for Logging. https://github.com/ggedde/spry-log

When using that Provider you can use these configuration settings:

    $config->logger = 'Spry\\SpryProvider\\SpryLog';
    $config->log_format = '%date_time% %ip% %path% - %msg%';
    $config->log_php_file = __DIR__.'/logs/php.log';
    $config->log_api_file = __DIR__.'/logs/api.log';
    $config->log_max_lines = 5000;
    $config->log_archive = false;
    $config->log_prefix = [
        'message' => 'Spry: ',
        'warning' => 'Spry Warning: ',
        'error' => 'Spry ERROR: ',
        'stop' => 'Spry STOPPED: ',
        'response' => 'Spry Response: ',
        'request' => 'Spry Request: '
    ];
	
## Endpoint
This is to direct the local spry server and cli when doing various tests.

	$config->endpoint = 'http://localhost:8000';
	
\* If you are using your own web server like nginx or apache and have a different url then change this accordingly.

## Components Directory
By default Spry comes with its own autoloader for SpryComponents which will look at any files in the this variable.

	$config->components_dir = __DIR__.'/components';
	
## Database
Spry comes with its own Provider for a Database connection: https://github.com/ggedde/spry-db

View the [SpryDB Configuration Settings](https://github.com/ggedde/spry-db) for full Documentation.

Spry's default Provider uses "Medoo" and you can find all the Documentation here: https://medoo.in/doc

You can change out the Provider for the DB connection in the configuration settings and use your own DB connector.

    $config->db = [
        'provider' => 'Spry\\SpryProvider\\SpryDB',
        'database_type' => 'mysql',
        'database_name' => '',
        'server' => 'localhost',
        'username' => '',
        'password' => '',
        'charset' => 'utf8',
        'port' => 3306,
        'prefix' => 'api_x_', // Should change this to be someting Unique
        'schema' => [
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

You can use this to build out or Modify your Database Schema.  
Using Spry CLI your can run

    spry m
    spry m --dryrun     (Show what changes will be made without running anything)
    spry m --force      (Run Destructively.  Will delete and change fields. You could loose precious data)


## Routes
An array of URL -> Compnent::Method

You can use simple Routes like this

    $config->routes = [
	    '/auth/login' => 'Auth::login',
        '/component/get' => 'Component::get'
    ]
    
Or you can provide more details per route like this.

    $config->routes = [
        '/example/get' => [
            'label' => 'Get Example',
            'controller' => 'Example::get',
            'public' => true,                   If Public then it will be available in Spry::get_routes()
            'active' => true,                   If Not Active then routing to this Method will Fail.
        ],
        '/example/get_all' => [
            'label' => 'Get All Examples',
            'controller' => 'Example::get_all',
            'public' => false,
            'active' => false,
        ]
    ]
   
   
## Response Codes
Here you can configure your response codes.

Default Codes:

    $config->response_codes = [
        2000 => ['en' => 'Success!'],
        4000 => ['en' => 'No Results'],
        5000 => ['en' => 'Error: Unknown Error'],
        5001 => ['en' => 'Error: Missing Config File'],
        5002 => ['en' => 'Error: Missing Salt in Config File'],
        5003 => ['en' => 'Error: Unknown configuration error on run'],

        5010 => ['en' => 'Error: No Parameters Found.'],
        5011 => ['en' => 'Error: Route Not Found.'],
        5012 => ['en' => 'Error: Class Not Found.'],
        5013 => ['en' => 'Error: Class Method Not Found.'],
        5014 => ['en' => 'Error: Returned Data is not in JSON format.'],
        5015 => ['en' => 'Error: Class Method is not Callable. Make sure it is Public.'],
        5016 => ['en' => 'Error: Controller Not Found.'],

        5020 => ['en' => 'Error: Field did not Validate.'],

        /* DB */
        2030 => ['en' => 'Database Migrate Ran Successfully'],
        5030 => ['en' => 'Error: Database Migrate had an Error'],
        5031 => ['en' => 'Error: Database Connect Error.'],
        5032 => ['en' => 'Error: Missing Database Credentials from config.'],
        5033 => ['en' => 'Error: Database Provider not found.'],

        /* Log */
        5040 => ['en' => 'Error: Log Provider not found.'],

        /* Tests */
        2050 => ['en' => 'Test Passed Successfully'],
        5050 => ['en' => 'Error: Test Failed'],
        5051 => ['en' => 'Error: Retrieving Tests'],
        5052 => ['en' => 'Error: No Tests Configured'],
        5053 => ['en' => 'Error: No Test with that name Configured'],

        /* Background Process */
        5060 => ['en' => 'Error: Background Process did not return Process ID'],
        5061 => ['en' => 'Error: Background Process could not find autoload'],
        5062 => ['en' => 'Error: Unknown response from Background Process'],
    ]
    
#### Multi-Language Support

    2000 => [
        'en' => 'Success!',
        'es' => '¡Éxito!'
    ]
    
#### Format - Success, Unknown and Error
The first number in the code represents the code type.
* [2]000 - the 2 represents 'Success'
* [4]000 - the 4 represents 'Unkown' or 'Empty'
* [5]000 - the 5 represents 'Error'
 
When using Spry::response() you can pass just the last 3 digits as the code and the data parameter.

Ex.
    
    Spry::response('000', $data);
    
* If $data has a value and is not empty then the response will automatically Prepend the code with a 2.
* If $data is an array but empty then the response will automatically Prepend the code with a 4.
* If $data is empty or null and not '0' then the response will automatically Prepend the code with a 5.


## Response Headers
Configure the Response headers back to the client

    $config->response_headers = [
        'Access-Control-Allow-Origin: *',
        'Access-Control-Allow-Methods: GET, POST, OPTIONS',
        'Access-Control-Allow-Headers: X-Requested-With, content-type'
    ];

## Tests
Spry comes with a built in Test module.

Here is how you can configure your own Tests:

    $config->tests = [
        'connection' => [
            'title' => 'Connection Test with Empty Parameters',
            'route' => '/testconnection',
            'params' => [],
            'expect' => [
                'response_code' => 5010,
            ]
        ],
        'connection2' => [
            'title' => 'Connection Test with Parameters',
            'route' => '/testconnection',
            'params' => [
                'test' => 123
            ],
            'expect' => [
                'response_code' => 5011,
            ]
        ],
    ];


Run the Tests through the CLI

    spry test
    spry test --verbose
    spry test connection --verbose --repeat 4
    spry test '{"route":"/example/add", "params":{"name":"test"}, "expect":{"response_code": 2000}}'
    
    
#### Using Values from the previous Test
You can use this format {\*.\*.\*.\*} while replacing the '\*' with the response keys. 

So if you wanted to use the last response 

    {
        body: {
            id: 123
        }
    } 
    
then you would use '{body.id}'

This is very usefull when wanting to Run tests that will Insert, Update, then Delete the item form the database that way you don't have any residual data after the tests.

Ex.  

    'item_insert' => [
        'label' => 'Create Item',
        'route' => '/items/insert',
        'params' => [
            'name' => 'Bob',
        ],
        'expect' => [
            'code' => 2102,
        ]
    ],
    'item_update' => [
        'label' => 'Update Item',
        'route' => '/items/update',
        'params' => [
            'id' => '{body.id}',
            'name' => 'Sammy',
        ],
        'expect' => [
            'code' => 2103,
        ]
    ],
    'item_delete' => [
        'label' => 'Delete Item',
        'route' => '/items/delete',
        'params' => [
            'id' => '{body.id}',
        ],
        'expect' => [
            'code' => 2104,
        ]
    ]

## Hooks and Filters
There are number of hooks and filters you can use to customize or manage the connection flow.  Each Hook and filter is an array of methods to call.

Ex.

    $config->hooks->configure = [
        'Spry\\SpryProvider\\SpryLog::setup_php_logs',
        'Spry\\SpryComponent\\SomeComponent::dosomething',
        'SomeOtherNameSpace\\SomeClass::someMethod'
    ];
    
Your components and methods will fire in order they are in the array.

#### Hooks
* $config->hooks->configure - Ran when Spry is done loading the Configuration
* $config->hooks->set_params - Ran when Spry is done setting the Params
* $config->hooks->set_routes - Ran when Spry is done setting the Routes
* $config->hooks->database - Ran when Spry is done connecting to the Database
* $config->hooks->stop - Ran when Spry is fires a Stop command.

#### Filters
Filters are like hooks but they require a return value.

* $config->filters->build_response - Ran when Spry creates the Response. Must return $response.
* $config->filters->response - Ran when Spry Sends the Response. Must return $response
* $config->filters->get_path - Ran when Spry retrieves a Path. Must return $path
* $config->filters->get_route - Ran when Spry retrieves a Route. Must return $route
* $config->filters->params - Ran when Spry retrieves the Params. Must return $params
* $config->filters->output - Ran when Spry Sends the Output. Must return $output
