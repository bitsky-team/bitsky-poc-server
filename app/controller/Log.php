<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Model\Log as LogModel;

    class Log extends Controller
    {
        public static function store($message, $level)
        {
            LogModel::create([
                'message' => $message,
                'level'   => $level
            ]);
        }
    }