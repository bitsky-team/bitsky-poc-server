<?php

namespace Controller;

use \Kernel\LogManager;
use \Model\User;

class Notification extends Controller
{
    public function create()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $authorizedForeign = $this->isAuthorizedForeign();

            if(
                !empty($_POST['sender_uniq_id']) &&
                !empty($_POST['receiver_uniq_id']) &&
                !empty($_POST['element_id']) &&
                !empty($_POST['element_type']) &&
                !empty($_POST['action']) &&
                !empty($_POST['link_id']) &&
                $authorizedForeign
            )
            {
                $sender = htmlspecialchars($_POST['sender_uniq_id']);
                $receiver = htmlspecialchars($_POST['receiver_uniq_id']);
                $elementId = htmlspecialchars($_POST['element_id']);
                $elementType = htmlspecialchars($_POST['element_type']);
                $action = htmlspecialchars($_POST['action']);
                $linkId = htmlspecialchars($_POST['link_id']);

                $notification = Notification::create([
                    'receiver_uniq_id' => $receiver,
                    'sender_uniq_id' => $sender,
                    'element_id' => $elementId,
                    'element_type' => $elementType,
                    'action' => $action,
                    'link_id' => $linkId
                ]);

                return json_encode(['success' => true, 'notification' => $notification]);
            } else
            {
                LogManager::store('[POST] Tentative de création de notifications avec des paramètres incorrects (ID utilisateur:  '.$check["uniq_id"].')', 2);
                return $this->forbidden('invalidParams');
            }
        } else
        {
            LogManager::store('[POST] Tentative de lecture des notifications d\'un utilisateur avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getAll()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $notifications = \Model\Notification::where('receiver_uniq_id', $check['uniq_id'])->get();

            foreach($notifications as $notification)
            {
                $notification['sender'] = null;

                if(!empty($notification->link_id))
                {
                    $linkController = new Link();
                    $_POST['link_id'] = $notification->link_id;
                    $link = json_decode($linkController->getLinkById(), true);
                    $link = $link['link'];

                    $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                    $bitsky_ip = $bitsky_ip['data'];

                    $notification['stranger'] = $bitsky_ip;

                    $owner = json_decode($this->callAPI(
                        'POST',
                        'http://localhost/get_user_by_uniq_id',
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'user_uniq_id' => $notification->sender_uniq_id,
                            'bitsky_ip' => $bitsky_ip
                        ]
                    ), true);

                    $notification['sender'] = $owner['user'];
                } else
                {
                    $notification['sender'] = User::where('uniq_id', $notification['sender_uniq_id'])->first();
                    unset($notification['sender']['password']);
                }

                // Get concerned users
                $notification['receiver'] = User::where('uniq_id', $notification['receiver_uniq_id'])->first();
                unset($notification['receiver']['password']);

                // Get concerned element
                switch($notification['element_type'])
                {
                    case 'post':
                        $notification['element_message'] = 'publication';
                        break;
                    case 'comment':
                        $notification['element_message'] = 'commentaire';
                        break;
                    default:
                        $notification['element'] = 'typeNotHandled';
                        break;
                }

                // Verbalize action
                switch($notification['action'])
                {
                    case 'addComment':
                        $notification['message'] = 'a commenté votre';
                        break;
                    case 'addFavorite':
                        $notification['message'] = 'a aimé votre';
                        break;
                    default:
                        $notification['message'] = 'actionNotHandled';
                        break;
                }
            }

            return json_encode(['success' => true, 'notifications' => $notifications]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération des notifications d\'un utilisateur avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function readAll()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $notifications = \Model\Notification::where('receiver_uniq_id', $check['uniq_id'])->get();

            foreach($notifications as $notification)
            {
                $notification->viewed = 1;
                $notification->save();
            }

            return json_encode(['success' => true]);
        } else
        {
            LogManager::store('[POST] Tentative de lecture des notifications d\'un utilisateur avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function count()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $notificationsCount = \Model\Notification::where('receiver_uniq_id', $check['uniq_id'])->where('viewed', 0)->count();
            return json_encode(['success' => true, 'count' => $notificationsCount]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération du nombre de notifications avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function deleteAll()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            \Model\Notification::where('receiver_uniq_id', $check['uniq_id'])->delete();
            return json_encode(['success' => true]);
        } else
        {
            LogManager::store('[POST] Tentative de suppression des notifications d\'un utilisateur avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }
}