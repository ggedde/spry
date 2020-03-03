# Spry
A lightweight PHP API Framework

Release: 1.0.0

REQUIRES:
* PHP 5.4

Included Packages:
* Medoo Database Class - http://medoo.in/
* Field Validation Class - https://github.com/blackbelt/php-validation
* Background Processes - https://github.com/cocur/background-process


# Initialize
To initialize Spry just include the init.php located in the spry folder. You will also need to add the composer autoloader if it has not already been added.

Example:
```php
include_once '/vendor/autoload.php';
include_once '/spry/init.php';
```

Or use the `run` method and include a path to your config file or include a config object.

Example:
```php
include_once '/vendor/autoload.php';
Spry::run('../config.php');
```
<br>

# Config

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
db | Array | [] |Database Object <br>[See Database documentation](#db)
dbProvider | String | 'Spry\\\\SpryProvider\\\\SpryDB' | Database Provider Class <br>[See Database documentation](#db)
endpoint | String | 'http://localhost:8000' | Spry Server Endpoint url. Used for internal requests only.
logger | Array | [] | Logger Object <br>[See Logger documentation](#Logger)
loggerProvider | Array | 'Spry\\SpryProvider\\SpryLogger' | Logger Provider Class <br>[See Logger documentation](#Logger)
responseCodes | Array | [] |Array of Response Codes <br>[See Response Codes documentation](#codes)
responseHeaders | Array | [] | Default Response Codes
routes | Array | [] | Array of Routes <br>[See Routes documentation](#routes)
salt | String | '' | Salt for Security. Change this to be Unique for each one of your API's. You should use a long and Strong key. DO NOT CHANGE IT ONCE YOU HAVE CREATED DATA. Otherwise things like logins may no longer work.
tests | Array | [] | Array of Tests <br>[See Tests documentation](#tests)

<br>

## Accessing Config Settings
---
You can access any setting by calling the config() object from Spry

Example: 
```php
echo Spry::config()->salt;
echo Spry::config()->db['database_name']
```
<br>

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
Method    |  Description
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
            2100 => 'Successfully Retrieved Item',
            4100 => 'No Item with that ID Found',
            5100 => 'Error: Retrieving Item',
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
        return Spry::response(100, $response);
    }
}
```


# Logger

Spry's default Log Provider is SpryLogger
 <br>[See SpryLogger's full documentation](https://github.com/ggedde/spry-logger)

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