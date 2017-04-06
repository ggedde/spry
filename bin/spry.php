<?php

include(dirname(dirname(__FILE__)).'/src/SpryApi.php');
include(dirname(dirname(__FILE__)).'/src/extensions/db.php');
include(dirname(dirname(__FILE__)).'/src/extensions/validator.php');
include(dirname(dirname(__FILE__)).'/src/extensions/tools.php');

class SpryApiCLI extends SpryApiTools {

    public static function run($config_file='')
    {
        $args = [];
        $config_file = 'config.php';
        $commands = ['hash', 'migrate', 'test'];
        $command = '';

        $hash = '';

        if(!empty($_SERVER['argv']))
        {
            $args = $_SERVER['argv'];
            $key = array_search('--config', $args);
            if($key !== false && isset($args[($key + 1)]))
            {
                $config_file = $args[($key + 1)];
            }

            $key = array_search('hash', $args);
            if($key !== false && isset($args[($key + 1)]))
            {
                $hash = $args[($key + 1)];
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

        parent::load_config($config_file);
        spl_autoload_register(array(__CLASS__, 'autoloader'));

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
                    echo 'Unknown Error';
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
                $response = parent::run_test($test='');
            break;
        }
    }
}

SpryApiCLI::run();
