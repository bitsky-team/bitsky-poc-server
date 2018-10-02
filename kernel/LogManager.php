<?php
    namespace Kernel;

    class LogManager
    {
        public static function store($message, $level)
        {
            if (!file_exists('../logs')) 
            {
                mkdir('../logs', 0777, true);
            }

            $logFile = '../logs/' . date('d-m-Y');
            $message = '['.date("H:i:s").'] Niveau ' . $level . ' => ' . $message;

            if (file_exists($logFile)) 
            {
                $fh = fopen($logFile, 'a');
                fwrite($fh, $message."\n");
            }else
            {
                $fh = fopen($logFile, 'w');
                fwrite($fh, $message."\n");
            }

            fclose($fh);
        }
    }