<?php

namespace Controller;

use \Controller\Auth;
use \Kernel\LogManager;
use \Model\User as UserModel;

class Log extends Controller
{
    public function __construct()
    {
        $this->authService = new Auth();
    } 

    public function get()
    {
        if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success)
            {
                $user = UserModel::where('uniq_id', $uniq_id)->first();

                if($user['rank'] == 2)
                {
                    $lines_content = [];
                    $dir = dirname(__FILE__).'/../../../logs';
                    $files = scandir($dir);

                    foreach ($files as $file) {
                        if(preg_match('/^[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}$/', $file)) {
                            $file_content = file_get_contents($dir.'/'.$file);
                            $lines = explode("\n", $file_content);

                            foreach($lines as $line) {
                                if(!empty($line)) {
                                    $line = ltrim($line, '[');
                                    $line = "[" . $file . " " . $line;
                                    array_push($lines_content, $line);
                                }
                            }
                        }
                    }
                    return json_encode(['success' => true, 'logs' => $lines_content]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération des logs avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('forbidden');
                }
            }else
            {
                LogManager::store('[POST] Tentative de récupération des logs avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                return $this->forbidden('invalidToken');
            }
        }else
        {
            return $this->forbidden('noInfos');
        }
    }
}