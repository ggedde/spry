<?php

namespace SpryApi\SpryCLI;

use SpryApi\Spry as Spry;
use SpryApi\SpryComponent\SpryTools as SpryTools;

include(dirname(dirname(__FILE__)).'/src/Spry.php');
include(dirname(dirname(__FILE__)).'/src/components/SpryDB.php');
include(dirname(dirname(__FILE__)).'/src/components/SpryLog.php');
include(dirname(dirname(__FILE__)).'/src/components/SpryValidator.php');
include(dirname(dirname(__FILE__)).'/src/components/SpryTools.php');

// Setup Server Vars for CLI
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

class SpryCLI extends SpryTools {

    public static function run()
    {
        $args = [];
        $config_file = 'config.php';
        $commands = ['hash', 'migrate', 'test'];
        $command = '';
        $test = '';
        $hash = '';
        $verbose = false;

        if(!empty($_SERVER['argv']))
        {
            $args = $_SERVER['argv'];
            $key = array_search('--config', $args);
            if($key !== false && isset($args[($key + 1)]))
            {
                $config_file = $args[($key + 1)];
            }

            $key = array_search('--verbose', $args);
            if($key !== false)
            {
                $verbose = true;
            }

            $key = array_search('hash', $args);
            if($key !== false && isset($args[($key + 1)]) && strpos($args[($key + 1)], '--') === false)
            {
                $hash = $args[($key + 1)];
            }

            $key = array_search('test', $args);
            if($key !== false && isset($args[($key + 1)]) && strpos($args[($key + 1)], '--') === false)
            {
                $test = $args[($key + 1)];
            }

            foreach ($args as $value)
            {
                if(in_array($value, $commands))
                {
                    $command = $value;
                }
            }
        }

        if(!$command)
        {
            die('No Command Found');
        }

        if(!$config_file || !file_exists($config_file))
        {
            die('No Config File Found. Run SpryCLI from the same folder that contains your "config.php" file or specify the config file with --config');
        }

        Spry::load_config($config_file);
        spl_autoload_register(['SpryApi\\Spry', 'autoloader']);

        switch($command)
        {
            case 'hash':
                if(!$hash)
                {
                    die('Missing Hash Value.  If hashing a value that has spaces then wrap with ""');
                }

                die(parent::get_hash($hash));

            break;

            case 'migrate':

                $migrate_args = [
                    'dryrun' => (in_array('--dryrun', $args) ? true : false),
                    'destructive' => (in_array('--destructive', $args) ? true : false),
                ];

                $response = parent::db_migrate($migrate_args);

                if(!empty($response['response']) && $response['response'] === 'error')
                {
                    if(!empty($response['messages']))
                    {
                        echo "ERROR:\n";
                        echo implode("\n", $response['messages']);
                    }
                }
                elseif(!empty($response['response']) && $response['response'] === 'success')
                {
                    if(!empty($response['body']))
                    {
                        echo "Success!\n";
                        echo implode("\n", $response['body']);
                    }
                }

            break;

            case 'test':

                if($test)
                {
                    $tests[] = $test;
                }
                else
                {
                    $tests = array_keys(Spry::config()->tests);
                }

                if(empty($tests))
                {

                }
                else
                {
                    foreach ($tests as $test)
                    {
                        echo "Running Test: ".$test."...\n";
                        $response = parent::test($test);
                        if(!empty($response['response']) && $response['response'] === 'error')
                        {
                            if(!empty($response['messages']))
                            {
                                echo "ERROR:\n";
                                echo implode("\n", $response['messages'])."\n";
                            }
                        }
                        elseif(!empty($response['response']) && $response['response'] === 'success')
                        {
                            if(!empty($response['body']))
                            {
                                echo "Success!\n";
                            }
                        }

                        if($verbose)
                        {
                            print_r($response);
                        }
                    }
                }


            break;
        }
    }
}

SpryCLI::run();
