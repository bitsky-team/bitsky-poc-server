<?php
    /*
    | = = = = = = = = = = = = = = = = 
    |   Register the autoloader
    | = = = = = = = = = = = = = = = = 
    */
    require_once __DIR__ . '/../vendor/autoload.php';

    /*
    | = = = = = = = = = = = = = = = =
    |   Load .env config
    | = = = = = = = = = = = = = = = =
    */
    
    $dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
    $dotenv->load();

    /*
    | = = = = = = = = = = = = =
    |  Enable CORS if wanted
    | = = = = = = = = = = = = =
    */

    if(boolval(getenv('ENABLE_CORS')))
    {
        header("Access-Control-Allow-Origin: *");
    }

    /*
    | = = = = = = = = = = = = = = = = = = = = = = = 
    |   Display errors if debug mode is enabled
    | = = = = = = = = = = = = = = = = = = = = = = =
    */

    if(boolval(getenv('APP_DEBUG')))
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    /*
    | = = = = = = = = = = = = = = = = 
    |   Booting Eloquent
    | = = = = = = = = = = = = = = = = 
    */

    Kernel\Kernel::bootEloquent();    

    /*
    | = = = = = = = = = = = = = = = = 
    |   Run the app
    | = = = = = = = = = = = = = = = = 
    */

    Kernel\Kernel::getInstance()::run();
