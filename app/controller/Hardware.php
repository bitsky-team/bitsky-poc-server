<?php
namespace Controller;

use \Kernel\JWT;
use \Kernel\LogManager;
use \Controller\Auth;
use \Model\User as UserModel;

class Hardware extends Controller
{
    public function __construct()
    {
        $this->authService = new Auth();
    }

    public function getTemp() 
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
                    $f = fopen("/sys/class/thermal/thermal_zone0/temp","r");
                    $temp = fgets($f);
                    $temp = round(intval($temp)/1000);
                    fclose($f);
                    return json_encode(['success' => true, 'temperature' => $temp]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération de la température avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('forbidden');
                }
            }else
            {
                LogManager::store('[POST] Tentative de récupération de la température avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                return $this->forbidden('invalidToken');
            }
        }else
        {
            return $this->forbidden('noInfos');
        }
    }
}