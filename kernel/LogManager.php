<?php
    namespace Kernel;

    class LogManager
    {
        public static function store($message, $level, $prepath = '/var/www/html/')
        {
            if(!getenv('NO_LOGS')) {
                $dir = $prepath . 'logs';

                if (!file_exists($dir))
                {
                    mkdir($dir, 0777, true);
                }

                $logFile = $dir . '/' . date('d-m-Y');
                $message = '['.date("H:i:s").'] Niveau ' . $level . ' => ' . $message;

                if(!file_exists($logFile)) touch($logFile);

                $fh = fopen($logFile, 'a');
                fwrite($fh, $message."\n");

                fclose($fh);
            }
        }
    }