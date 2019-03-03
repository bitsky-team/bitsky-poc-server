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

    public function getCPUUsage()
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
                    $stat1 = file('/proc/stat'); 
                    sleep(1); 
                    $stat2 = file('/proc/stat'); 
                    $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0])); 
                    $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0])); 
                    $dif = array(); 
                    $dif['user'] = $info2[0] - $info1[0]; 
                    $dif['nice'] = $info2[1] - $info1[1]; 
                    $dif['sys'] = $info2[2] - $info1[2]; 
                    $dif['idle'] = $info2[3] - $info1[3]; 
                    $total = array_sum($dif); 
                    $cpu = array(); 
                    foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);
                    return json_encode(['success' => true, 'cpu_usage' => $cpu['user'] + $cpu['nice'] + $cpu['sys']]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération du CPU avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('forbidden');
                }
            }else
            {
                LogManager::store('[POST] Tentative de récupération du CPU avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                return $this->forbidden('invalidToken');
            }
        }else
        {
            return $this->forbidden('noInfos');
        }
    }

    public function getLinkingKey()
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
                    $linkingKey = getenv('LINKING_KEY');
                    return json_encode(['success' => true, 'key' => $linkingKey]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération du CPU avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('forbidden');
                }
            }else
            {
                LogManager::store('[POST] Tentative de récupération du CPU avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                return $this->forbidden('invalidToken');
            }
        }else
        {
            return $this->forbidden('noInfos');
        }
    }
}