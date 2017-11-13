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

This file contains all the configuration for Spry

#### Salt
This Variable contains the salt that is used with the "hash()" method to create hashes.
	$config->salt = '1234567890abcdefghijklmnopqrstuvwxyz';
\* WARNING:  changing this after data is stored to your database will result in errors.  It is best NOT to change this after any data has been stored.

