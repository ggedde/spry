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

### Salt
This Variable contains the salt that is used with the "hash()" method to create hashes.

	$config->salt = '1234567890abcdefghijklmnopqrstuvwxyz';
	
\* WARNING:  changing this after data is stored to your database will result in errors.  It is best NOT to change this after any data has been stored.

### Logging
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
	
### Endpoint
This is to direct the local spry server and cli when doing various tests.

	$config->endpoint = 'http://localhost:8000';
	
\* If you are using your own web server like nginx or apache and have a different url then change this accordingly.

### Components Directory
By default Spry comes with its own autoloader for SpryComponents which will look at any files in the this variable.

	$config->components_dir = __DIR__.'/components';
	
### Database
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
