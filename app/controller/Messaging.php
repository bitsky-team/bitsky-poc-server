<?php

namespace Controller;
use \Kernel\LogManager;
use Model\Conversation;
use Model\User;
use Model\Message;
use Controller\User as UserController;

class Messaging extends Controller
{
    public function __construct()
    {
        $this->authService = new Auth();
    }

    public function createConversation()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['first_user_id']) && !empty($_POST['second_user_id']))
            {
                $firstUserID = htmlspecialchars($_POST['first_user_id']);
                $secondUserID = htmlspecialchars($_POST['second_user_id']);

                $firstUser = User::find($firstUserID);
                $secondUser = User::find($secondUserID);

                $bitskyIp = (!empty($_POST['bitsky_ip'])) ? $_POST['bitsky_ip'] : null;

                if($bitskyIp && empty($secondUser)) {
                    $userController = new UserController();

                    $_POST['uniq_id'] = $check['uniq_id'];
                    $_POST['bitsky_ip'] = $bitskyIp;
                    $_POST['user_id'] = $secondUserID;

                    $secondUser = json_decode($userController->strangerGetById(), true);
                    $secondUser = $secondUser['user'];
                }

                $conversation = Conversation::where(function ($query) use ($firstUser) {
                    $query->where('first_user_uniq_id', $firstUser['uniq_id'])
                          ->orWhere('second_user_uniq_id', $firstUser['uniq_id']);
                })->where(function ($query) use ($secondUser) {
                    $query->where('first_user_uniq_id', $secondUser['uniq_id'])
                          ->orWhere('second_user_uniq_id', $secondUser['uniq_id']);
                })->first();

                if($bitskyIp)
                {
                    // http://$bitskyIp/getConversation
                }

                if(empty($conversation))
                {
                    $linkId = null;

                    if($bitskyIp)
                    {
                        $linkController = new Link();
                        $key = json_decode($linkController->getKeyOfIp($bitskyIp), true);
                        $key = $key['data'];
                        $link = \Model\Link::where('bitsky_key', $key)->first();
                        $linkId = $link->id;
                    }

                    $conversation = Conversation::create([
                        'first_user_uniq_id' => $firstUser['uniq_id'],
                        'second_user_uniq_id' => $secondUser['uniq_id'],
                        'link_id' => $linkId
                    ]);
                }

                return json_encode(['success' => true, 'conversation' => $conversation]);
            } else
            {
                LogManager::store('[POST] Tentative création de conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('noInfos');
            }
        } else
        {
            LogManager::store('[POST] Tentative création de conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getConversations()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $conversations = Conversation::where(function ($query) use ($check) {
                $query->where('first_user_uniq_id', $check['uniq_id'])
                    ->orWhere('second_user_uniq_id', $check['uniq_id']);
            })->get();

            foreach($conversations as $conversation)
            {
                if($check['uniq_id'] == $conversation->first_user_uniq_id)
                {
                    $user = User::where('uniq_id', $conversation->second_user_uniq_id)->first(['uniq_id', 'firstname', 'lastname', 'avatar']);
                    unset($conversation->first_user_uniq_id);
                    unset($conversation->second_user_uniq_id);
                    $conversation->user = $user;
                }

                if($check['uniq_id'] == $conversation->second_user_uniq_id)
                {
                    $user = User::where('uniq_id', $conversation->first_user_uniq_id)->first(['uniq_id', 'firstname', 'lastname', 'avatar']);
                    unset($conversation->first_user_uniq_id);
                    unset($conversation->second_user_uniq_id);
                    $conversation->user = $user;
                }

                $conversation->lastMessage = Message::where('conversation_id', $conversation->id)->orderBy('created_at', 'desc')->first();

                if(!empty($conversation->lastMessage) && $conversation->lastMessage->receiver_uniq_id == $check['uniq_id'] && $conversation->lastMessage->receiver_read == 0)
                {
                    $conversation->unread = true;
                } 
            }



            return json_encode(['success' => true, 'conversations' => $conversations]);
        } else
        {
            LogManager::store('[POST] Tentative récupération des conversations avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getConversation()
    {
        $check = $this->checkUserToken();

        if($check)
        {
            $actualUser = User::where('uniq_id', $check['uniq_id'])->first();
            $id = htmlspecialchars($_POST['conversation_id']);
            $conversation = Conversation::where('id', $id)->first();

            if($check['uniq_id'] == $conversation->first_user_uniq_id || $check['uniq_id'] == $conversation->second_user_uniq_id || $actualUser->rank == 2)
            {
                if($check['uniq_id'] == $conversation->first_user_uniq_id)
                {
                    $user = User::where('uniq_id', $conversation->second_user_uniq_id)->first(['id', 'uniq_id', 'firstname', 'lastname', 'avatar']);
                    unset($conversation->first_user_uniq_id);
                    unset($conversation->second_user_uniq_id);
                    $conversation->user = $user;
                }

                if($check['uniq_id'] == $conversation->second_user_uniq_id)
                {
                    $user = User::where('uniq_id', $conversation->first_user_uniq_id)->first(['id', 'uniq_id', 'firstname', 'lastname', 'avatar']);
                    unset($conversation->first_user_uniq_id);
                    unset($conversation->second_user_uniq_id);
                    $conversation->user = $user;
                }

                $messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at', 'asc')->get();

                foreach($messages as $message)
                {
                    if($message->receiver_uniq_id == $check['uniq_id'])
                    {
                        $messageToUpdate = Message::where('id', $message->id)->first();
                        $messageToUpdate->receiver_read = 1;
                        $messageToUpdate->save();
                    }
                }

                $conversation->messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at', 'asc')->get();
                return json_encode(['success' => true, 'conversation' => $conversation]);
            }else
            {
                LogManager::store('[POST] Tentative récupération d\'une conversation sans permission (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('noPermission');
            }
        }else
        {
            LogManager::store('[POST] Tentative récupération d\'une conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function sendMessage()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['conversation_id']) && !empty($_POST['receiver_uniq_id']) && !empty($_POST['content']))
            {
                $conversationId = htmlspecialchars($_POST['conversation_id']);
                $receiverUniqId = htmlspecialchars($_POST['receiver_uniq_id']);
                $content = htmlspecialchars($_POST['content']);

                $conversation = Conversation::find($conversationId);

                if(!empty($conversation))
                {
                    $message = Message::create([
                        'conversation_id' => $conversationId,
                        'sender_uniq_id' => $check['uniq_id'],
                        'receiver_uniq_id' => $receiverUniqId,
                        'content' => $content,
                    ]);

                    return json_encode(['success' => true, 'message' => $message]);
                } else
                {
                    LogManager::store('[POST] Tentative d\'envoi de message avec de mauvaises informations (ID utilisateur: '.$check['uniq_id'].')', 2);
                    return $this->forbidden('noConversation');
                }
            } else
            {
                LogManager::store('[POST] Tentative d\'envoi de message avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('noInfos');
            }
        } else
        {
            LogManager::store('[POST] Tentative d\'envoi de message avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function countUnreadMessages()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $count = 0;

            $conversations = Conversation::where(function ($query) use ($check) {
                $query->where('first_user_uniq_id', $check['uniq_id'])
                    ->orWhere('second_user_uniq_id', $check['uniq_id']);
            })->get();

            foreach($conversations as $conversation)
            {
                $count += Message::where('conversation_id', $conversation->id)
                    ->where('receiver_uniq_id', $check['uniq_id'])
                    ->where('receiver_read', 0)->count();
            }

            return json_encode(['success' => true, 'count' => $count]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération du nombre de message avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }
}