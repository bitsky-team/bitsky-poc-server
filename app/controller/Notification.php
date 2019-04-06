<?php

namespace Controller;

use \Kernel\LogManager;
use \Model\User;
use \Model\Post;
use \Model\PostComment;


class Notification extends Controller
{
    public function getAll()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $notifications = \Model\Notification::where('receiver_uniq_id', $check['uniq_id'])->get();

            foreach($notifications as $notification)
            {
                // Get concerned users
                $notification['sender'] = User::where('uniq_id', $notification['sender_uniq_id'])->first();
                unset($notification['sender']['password']);
                $notification['receiver'] = User::where('uniq_id', $notification['receiver_uniq_id'])->first();
                unset($notification['receiver']['password']);

                // Get concerned element
                switch($notification['element_type'])
                {
                    case 'post':
                        $notification['element'] = Post::where('id', $notification['element_id'])->first();
                        $notification['elementMessage'] = 'publication';
                        break;
                    case 'comment':
                        $notification['element'] = PostComment::where('post_id', $notification['element_id'])->first();
                        $notification['elementMessage'] = 'commentaire';
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