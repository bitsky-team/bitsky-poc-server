<?php

    namespace Kernel;

    use Illuminate\Database\Capsule\Manager as Capsule;  

    /**
     * RestPHP's core
     * 
     * @category Core
     * @author Jason Van Malder <jasonvanmalder@gmail.com>
     */
    class Kernel 
    {
        /**
         * @var Kernel
         * @access private
         * @static
         */
        private static $_instance = null;
        
        /**
         * Method who creates the class instance
         * if it doesn't exists yet then return it
         *
         * @param void
         * @return Kernel
         */
        public static function getInstance() : Kernel
        {
            if(is_null(self::$_instance))
            {
                self::$_instance = new Kernel();  
            }
        
            return self::$_instance;
        }

        /**
         * This method will do all the jobs
         * before launching the router
         * 
         * @param void
         * @return void
         */
        public static function run()
        {            
            Router::launch();
        }

        /**
         * Method used for eloquent booting
         * 
         * @return void
         */
        public static function bootEloquent()
        {
            $capsule = new Capsule;

            $capsule->addConnection(array(
                'driver'    => 'mysql',
                'host'      => getenv('DB_HOST'),
                'database'  => getenv('DB_DATABASE'),
                'username'  => getenv('DB_USERNAME'),
                'password'  => getenv('DB_PASSWORD'),
                'port'      => getenv('DB_PORT'),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => ''
            ));

            $capsule->bootEloquent();
        }

        /**
         * Method who creates a uuid
         * 
         * @return string
         */
        public static function generate_uuid() {
            $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0C2f ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0x2Aff ), mt_rand( 0, 0xffD3 ), mt_rand( 0, 0xff4B )
            );

            return strtoupper($uuid);
        }
    } 
