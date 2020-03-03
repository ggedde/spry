# Spry
A lightweight PHP API Framework

![GitHub release (latest by date including pre-releases)](https://img.shields.io/github/v/release/ggedde/spry?include_prereleases) &nbsp; ![GitHub](https://img.shields.io/github/license/ggedde/spry?label=license) &nbsp; ![PHP from Packagist](https://img.shields.io/packagist/php-v/ggedde/spry)


Included Packages:
* Medoo Database Class - http://medoo.in/
* Field Validation Class - https://github.com/blackbelt/php-validation
* Background Processes - https://github.com/cocur/background-process

# Documentation

* [Installation](#Installation)
* [Quick Start](#QuickStart)
* [Configuration](#Configuration)
* [Components](#Components)
* [Routes](#Routes)
* [Database](#Database)
* [Logger](#Logger)
* [Response Codes](#ResponseCodes)
* [Tests](#Tests)
* [Hooks](#Hooks)
* [Filters](#Filters)

# Installation

The best way to install Spry and use it is through the CLI.
https://github.com/ggedde/spry-cli

    composer global require ggedde/spry-cli

Please reference the [Installation Process](https://github.com/ggedde/spry-cli#installation) on the CLI Page.

	spry new [project_name]
	cd [project_name]

To initialize Spry just include the `init.php` file located in the spry folder.  
You will need to add the composer autoloader if it has not already been added.  

Example:
```php
include_once '/vendor/autoload.php';
include_once '/spry/init.php';
```
\* Running `spry new [projectName]` will automatically create a `public` folder with the includes already added for you.

<br>

## Manual Installation

    composer require ggedde/spry

You will need to add the composer autoloader if it has not already been added.
Then use the `run` method and include a path to your config file or include a config object.

Example:
```php
include_once '/vendor/autoload.php';
Spry\Spry::run('../config.php');
```

# QuickStart

### Create a project through the CLI
    spry new [project_name]
	cd [project_name]

### Folder Structure

    - public/
        index.php
    - spry/
        - components/
        - logs/
            - api.log
            - php.log
        - config.php
        - init.php

### To Start the Spry test server run

	spry up

### Then open a separate termal and run some tests

	spry test

### Create a Component

	spry component MyComponent
<small>*view/edit `spry/components/MyComponent.php`*</small>

### Update Database Scheme from New Component to Database

	spry migrate

### Run Tests again with new Component

	spry test

# Configuration

Spry requires a config file or a config object.

When using a config file Spry will pass a pre-initialized $config object to the file.  You will just need to set the variables within the object

Example Config File:
```php
<?php
$config->salt = '';
$config->endpoint = 'http://localhost:8000';
$config->componentsDir = __DIR__.'/components';
...
```

Setting | Type | Default | Description
-------|--------|-------------|-----------
componentsDir | String| \_\_DIR\_\_.'/components' | Directory where Components are stored. Component Filenames must match the Class name of the Component.
db | Array | [] |Database Object <br>[See Database documentation](#Database)
dbProvider | String | 'Spry\\\\SpryProvider\\\\SpryDB' | Database Provider Class <br>[See Database documentation](#Database)
endpoint | String | 'http://localhost:8000' | Spry Server Endpoint url. Used for internal requests only.
logger | Array | [] | Logger Object <br>[See Logger documentation](#Logger)
loggerProvider | Array | 'Spry\\\\SpryProvider\\\\SpryLogger' | Logger Provider Class <br>[See Logger documentation](#Logger)
projectPath | String | \_\_DIR\_\_ | Path to Spry Project. Default is to use the same directory the config.php file is in. 
responseCodes | Array | [] | Array of Response Codes <br>[See Response Codes documentation](#ResponseCodes)
responseHeaders | Array | [] | Default Response Codes
routes | Array | [] | Array of Routes <br>[See Routes documentation](#Routes)
salt | String | '' | Salt for Security. Change this to be Unique for each one of your API's. You should use a long and Strong key. DO NOT CHANGE IT ONCE YOU HAVE CREATED DATA. Otherwise things like logins may no longer work.
tests | Array | [] | Array of Tests <br>[See Tests documentation](#Tests)

## Accessing Config Settings
You can access any setting by calling the config() object from Spry

Example: 
```php
echo Spry::config()->salt;
echo Spry::config()->db['database_name']
```

## Extending Config Settings
---
You can add your own settings and then access them later in your component, provider or plugin

config.php
```php
$config->mySetting = '123';
```

MyComponent.php
```php
echo Spry::config()->mySetting;
```

# Components
Spry is mainly used through components added in the `componentsDir` set in the [Spry config](#Config).

Components are classes within the SpryComponent Namespace. 
<br>Components can set their own Routes, Response Codes, DB Schema and Tests by using built in methods. Or you can configure everything through the config.php

These built in Methods are Optional:

Method | Description
----------|------------------------------------
setup     |  *Hook on initial Spry setup*
getCodes  |  *Returns Array of Codes and adds them to the Config*
getRoutes |  *Returns Array of Routes and adds them to the Config*
getSchema |  *Returns Array of Table Schema and adds them to the Config*
getTests  |  *Returns Array of Tests and adds them to the Config*

Any Methods that resolve a route is called a Controller and gets passed any Parameters from the route.


Basic Example:
```php
<?php

namespace Spry\SpryComponent;

class MyComponent
{
    private static $id = 2; // Component ID

    public static function setup() {
        Spry::addFilter('configure', 'MyComponent::stuff');
    }

    public static function stuff($config) {
        // Do Stuff
        return $config;
    }

    public static function getRoutes() {
        return [
            '/items/{id}' => [
                'label' => 'Get Item',
                'controller' => 'MyComponent::get',
                'access' => 'public',
                'methods' => 'GET',
                'params' => [
                    'id' => [
                        'required' => true,
                        'type' => 'int',
                    ],
                ],
            ],
        ];
    }
    public static function getCodes() {
        return [
            self::$id => [
                201 => ['en' => 'Successfully Retrieved Item'],
                401 => ['en' => 'No Item with that ID Found'],
                501 => ['en' => 'Error: Retrieving Item'],
            ],
        ];
    }
    public static function getSchema() {
        return [
            'items' => [
                'columns' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }
    public static function getTests() {
        return [
            'items_get' => [
                'label' => 'Get Example',
                'route' => '/items/123',
                'params' => [],
                'expect' => [
                    'status' => 'success',
                ],
            ],
        ];
    }

    public static function get($params = [])
    {
        $response = Spry::db()->get('items', '*', $params)
        return Spry::response(self::$id, 01, $response);
    }
}
```

# Routes

When using single file components you can set the routes in the Components by returning the routes in the `getRoutes()` method.

Example:
```php
public static function getRoutes() {
    return [
        '/items/{id}' => [
            'label' => 'Get Item',
            'controller' => 'MyComponent::get',
            'access' => 'public',
            'methods' => 'GET',
            'params' => [
                'id' => [
                    'required' => true,
                    'type' => 'int',
                ],
            ],
        ],
    ];
}
```

### Or within the Config Settings
```php
$config->routes[
    '/items/{id}' => [
        'label' => 'Get Item',
        'controller' => 'MyComponent::get',
        'access' => 'public',
        'methods' => 'GET',
        'params' => [
            'id' => [
                'required' => true,
                'type' => 'int',
            ],
        ],
    ],
    ...
];
```

## Route Options

Setting | Type | Default | Description
-------|--------|-------------|-----------
label | String | Path Sanitized | Name used to Label Route
controller | String | '' | Path to Component Method.  Ex. `MyComponent::method` or `NameSpace\\Framework\\Class::someMethod`
access | String | public | Spry has a getRoutes method which will return all public routes. You can remove the route from that method by setting the access to `private` 
methods | String\|Array | POST | Method allowed for route. POST\|GET\|DELETE\|PUT
params | Array | [] | Allowed Parameters for the Route. Any Parameters passed that do not match will get ignored. You can specify Validation for each Parameter.
params_trim | Boolean | false | Trim all Params for surrounding whitespace. This can be overwritten per param.

## Param Options

Setting | Type | Default | Description
-------|--------|-------------|-----------
type | String | string | Types: integer \| number \| float \| array \| cardNumber \| date \| email \| url \| ip \| domain \| string \| boolean \| password
between         | Array[min,max] | [] | Validates Param is between 2 values
betweenLength   | Array[min,max] | [] | Validates Param length is between 2 values
callback        | Callback |  | Custom Callback function to return `true` for valid. Can be array for array(CLASS, METHOD) or closure.
endsWith        | String | '' | Validates Param ends with a substr.
filter          | Callback |  | Closure to filter value. Closure accepts one argument of the value and must return filtered value.
has             | String | '' | Checks if Param is array and contains value. 
hasLetters      | Integer | 1 | Validates Param contains x number of letters [a-zA-Z]
hasLowercase    | Integer | 1 | Validates Param contains x number of lower case letters [a-z]
hasNumbers      | Integer | 1 | Validates Param contains x number of numbers [0-9]
hasSymbols      | Integer | 1 | Validates Param contains x number of Symbols
hasUppercase    | Integer | 1 | Validates Param contains x number of upper case letters [A-Z]
in              | Array | [] | Validates Param is within Array
length          | Interger |  | Length of Param must match value. Accepts Length of Array or String
matches         | String | '' | Validates Param matches Value. Strict Type comparison is enabled.
max             | Integer |  | Maximum count of Array or length of String
maxDate         | Integer \| String | 0 | If Integer then maximum days from today or String of Date formatted
maxLength       | Integer |  | Maximum length of String for Parameter.  Strings only.
min             | Integer |  | Minimum count of Array or length of String
minDate         | Integer \| String | 0 | If Integer then minimum days from today or String of Date formatted
minLength       | Integer |  | Maximum length of String for Parameter.  Strings only.
notEndsWith     | String | '' | Validates Param does not end with a substr.
notMatches      | String | '' | Validates Param does not match Value. Strict Type comparison is enabled.
notStartsWith   | String | '' | Validates Param does not start with a substr.
numbersOnly     | Boolean | true | Validates Param only contains numbers.
required        | Boolean \| Array | true | Validates Param has a Value. if Array then Only requires if params within required array have value (conditional checking).
startsWith      | String | '' | Validates Param starts with a substr.
trim            | Boolean | true | Trims Param for surrounding whitespace
validateOnly    | Boolean | true | Validates the param, but does not send the value back to the controller.  Ex.  good for 'confirm_password' which doesn't need to be returned to the controller, but present for 'password' to match it.
unique          | Boolean | true | If Param is Array then duplicate values within array will be removed.


# Database

Spry's default Database Provider is SpryDB based on Medoo
 <br>[See SpryDB's full documentation](https://github.com/ggedde/spry-db)

This allows you to swap out the Provider later on without having to change your project code.

```php
Spry::db()->get('items', '*', ['id' => 123]);
Spry::db()->select('items', '*', ['date[>]' => '2020-01-01']);
Spry::db()->insert('items', ['name' => 'test', 'date' => '2020-01-01']);
Spry::db()->update('items', ['name' => 'newtest'], ['id' => 123]);
Spry::db()->delete('items', ['id' => 123]);
```

### Spry Config Settings
```php
$config->dbProvider = 'Spry\\SpryProvider\\SpryDB';
$config->db = [... ];
```
  
# Logger

Spry's default Log Provider is SpryLogger
 <br>[See SpryLogger's full documentation](https://github.com/ggedde/spry-log)

This allows you to swap out the Provider later on without having to change your project code.

```php
Spry::log()->message("My Message");
Spry::log()->warning("Warning");
Spry::log()->error("Error");
```

## Spry Configuration

```php
$config->loggerProvider = 'Spry\\SpryProvider\\SpryLogger';
$config->logger = [... ];
```


# Tests

Spry comes with its own pre-built testing solution. You can still use other like PHPUnit, but Spry's Tests utilize's Spry's config for rapid testing. When using single file components you can set the routes in the Components by returning the routes in the `getTests()` method.

Example:
```php
public static function getTests() {
    return [
        'items_insert' => [
            'label' => 'Insert Item',
            'route' => '/items/insert',
            'method' => 'POST',
            'params' => [
                'name' => 'TestData',
            ],
            'expect' => [
                'status' => 'success',
            ],
        ],
        'items_get' => [
            'label' => 'Get Item',
            'route' => '/items/{items_insert.body.id}',
            'method' => 'GET',
            'params' => [],
            'expect' => [
                'status' => 'success',
            ],
        ],
        'items_delete' => [
            'label' => 'Delete Item',
            'route' => '/items/delete/',
            'method' => 'POST',
            'params' => [
                'id' => '{items_insert.body.id}'
            ],
            'expect' => [
                'status' => 'success',
            ],
        ],
    ];
}
```

### Or within the Config Settings
```php
$config->tests[
    'items_get' => [
        'label' => 'Get Example',
        'route' => '/items/123',
        'params' => [],
        'expect' => [
            'code' => '1-200',
        ],
    ],
    'items_get' => [
        'label' => 'Get Example',
        'route' => '/items/123',
        'params' => [],
        'expect' => [
            'body.id[>]' => 12,
        ],
    ],
    ...
];
```

# ResponseCodes

Example:
```php
public static function getCodes()
{
    return [
        self::$id => [
            201 => ['en' => 'Successfully Retrieved Item'],
            401 => ['en' => 'No Item with that ID Found'],
            501 => ['en' => 'Error: Retrieving Item'],

            202 => ['en' => 'Successfully Retrieved Items'],
            402 => ['en' => 'No Results Found'],
            502 => ['en' => 'Error: Retrieving Items'],

            203 => ['en' => 'Successfully Created Item'],
            503 => ['en' => 'Error: Creating Item'],

            204 => ['en' => 'Successfully Updated Item'],
            404 => ['en' => 'No Item with that ID Found'],
            504 => ['en' => 'Error: Updating Item'],

            205 => ['en' => 'Successfully Deleted Item'],
            505 => ['en' => 'Error: Deleting Item'],
        ],
    ];
}
```

### Or within the Config Settings
```php
$config->tests[
    1 => [
        201 => ['en' => 'Successfully Retrieved Item'],
        401 => ['en' => 'No Item with that ID Found'],
        501 => ['en' => 'Error: Retrieving Item'],

        202 => ['en' => 'Successfully Retrieved Items'],
        402 => ['en' => 'No Results Found'],
        502 => ['en' => 'Error: Retrieving Items'],

        203 => ['en' => 'Successfully Created Item'],
        503 => ['en' => 'Error: Creating Item'],

        204 => ['en' => 'Successfully Updated Item'],
        404 => ['en' => 'No Item with that ID Found'],
        504 => ['en' => 'Error: Updating Item'],

        205 => ['en' => 'Successfully Deleted Item'],
        505 => ['en' => 'Error: Deleting Item'],
    ],
    2 => [
        201 => ['en' => 'Successfully Retrieved Other Item'],
        401 => ['en' => 'No Other Item with that ID Found'],
        501 => ['en' => 'Error: Retrieving Other Item'],

        202 => ['en' => 'Successfully Retrieved Other Items'],
        402 => ['en' => 'No Results Found'],
        502 => ['en' => 'Error: Retrieving Other Items'],

        203 => ['en' => 'Successfully Created Other Item'],
        503 => ['en' => 'Error: Creating Other Item'],

        204 => ['en' => 'Successfully Updated Other Item'],
        404 => ['en' => 'No Other Item with that ID Found'],
        504 => ['en' => 'Error: Updating Other Item'],

        205 => ['en' => 'Successfully Deleted Other Item'],
        505 => ['en' => 'Error: Deleting Other Item'],
    ],
    ...
];
```

## Component Group Number
The first number is to separate the Components codes by ID
Ex.

    1-200   # Success For Component A 
    2-200   # Success For Component B 

## Multi-Language Support

    1 => [
        200 => [
            'en' => 'Success!',
            'es' => '¡Éxito!'
        ]
    ]

### Format - Info, Success, Client Error and Server Error
The first number in the code represents the code type.

1-[1]00 - the 1 represents 'Info'  
1-[2]00 - the 2 represents 'Success'  
1-[4]00 - the 4 represents 'Client Error', Unkown' or 'Empty'  
1-[5]00 - the 5 represents 'Server Error'  
When using Spry::response() you can pass just the last 2 digits as the code and the data parameter.

Ex.

    Spry::response(1, '00', $data); 

If $data **has** a value and is **not empty** then the response will automatically Prepend the code with a **2** and return **1-200**.  
If $data is an array but **empty** then the response will automatically Prepend the code with a **4** and return **1-400**.  
If $data is **empty** or **null** and not '0' then the response will automatically Prepend the code with a **5** and return **1-500**.

## Test Options

Setting | Type | Default | Description
-------|--------|-------------|-----------
label | String | '' | Name used to Label Test
route | String | '' | Route that is enabled in Spry Routes
method | String | GET | Route that is enabled in Spry Routes
params | Array | [] | Params passed in Route
expect | Array | [] | Key and Value of what is expected valid from the Response. Keys accept dot notation and [>], [>=], [<], [<=], [!=], [===], [!==] comparison checks. See above.


## Running Tests with Spry CLI
All Tests

    spry test

Specific Test

    spry test items_get

More Options

    spry test --verbose --repeat 10

# Hooks

Hooks allow you to run your own code at specific times and life cycles. 

### Adding Hooks
```php
Spry::addHook(string $hookName, string|callable $controller, [ mixed $extraData = null [, int $order = 0 ]] ) : void
```

Example:

```php
Spry::addHook('configure', 'Spry\\SpryComponent\\MyComponent::myMethod', ['bar' => 345], 100);
```

### Running Hooks
```php
Spry::runHook(string $hookName, [ mixed $data ] ) : void
```
Example:

```php
Spry::runHook('configure',['foo' => 123]);
```
Your Controller 
```php
class MyComponent

public static function myMethod($data = null, $extraData = null) {
    $data // [foo => 123]
    $extraData // [bar => 345]
    // Do Stuff
}
```

### Available Hooks
- configure
- database
- setParams
- setPath
- setRoutes
- stop

<br>

# Filters
Filters allow you to filter data at specific times and life cycles. A filter typically requires a return value. 

### Adding Filters
```php
Spry::addFilter(string $filterName, string|callable $controller, [ mixed $extraData = null [, int $order = 0 ]] ) : void
```

Example:

```php
Spry::addFilter('configure', 'Spry\\SpryComponent\\MyComponent::myMethod', ['bar' => 345], 100);
```

### Running Filters
```php
Spry::runFilter(string $filterName, [ mixed $data ] ) : void
```
Example:

```php
$config = Spry::runFilter('configure', $config);
```
Your Controller 
```php
class MyComponent

public static function myMethod($config = null, $extraData = null) {
    $config // $config
    $extraData // [bar => 345]
    // Do Stuff
    return $config;
}
```

### Available Filters
- buildResponse
- configure
- getPath
- getRoute
- output
- params
- response
- validateParams
