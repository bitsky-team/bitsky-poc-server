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

    public function checkConversationExists()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            if(!empty($_POST['first_user']) && !empty($_POST['second_user']))
            {
                $first = htmlspecialchars($_POST['first_user']);
                $second = htmlspecialchars($_POST['second_user']);

                if(empty($_POST['bitsky_ip']))
                {
                    return Conversation::where(function ($query) use ($first) {
                        $query->where('first_user_uniq_id', $first)
                            ->orWhere('second_user_uniq_id', $first);
                    })->where(function ($query) use ($second) {
                        $query->where('first_user_uniq_id', $second)
                            ->orWhere('second_user_uniq_id', $second);
                    })->first();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/check_conversation_exists';

                    $request = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'first_user' => $_POST['first_user'],
                            'second_user' => $_POST['second_user']
                        ]
                    );

                    $request = json_decode($request, true);

                    return $request;
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération de conversation sans donner d\'information (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération de conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }


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

                $_POST['first_user'] = $firstUser['uniq_id'];
                $_POST['second_user'] = $secondUser['uniq_id'];

                $conversation = $this->checkConversationExists();

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
                LogManager::store('[POST] Tentative de création de conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('noInfos');
            }
        } else
        {
            LogManager::store('[POST] Tentative de création de conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalConversations()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            $postData = $_POST;
            $conversations = Conversation::where(function ($query) use ($postData) {
                $query->where('first_user_uniq_id', $postData['uniq_id'])
                    ->orWhere('second_user_uniq_id', $postData['uniq_id']);
            })->get();

            foreach($conversations as $conversation)
            {
                if($_POST['uniq_id'] == $conversation->first_user_uniq_id)
                {
                    if(!empty($conversation->link_id))
                    {
                        $linkController = new Link();
                        $_POST['link_id'] = $conversation->link_id;
                        $link = json_decode($linkController->getLinkById(), true);
                        $link = $link['link'];

                        $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                        $bitsky_ip = $bitsky_ip['data'];

                        $user = json_decode($this->callAPI(
                            'POST',
                            'http://localhost/get_user_by_uniq_id',
                            [
                                'uniq_id' => $_POST['uniq_id'],
                                'token' => $_POST['token'],
                                'user_uniq_id' => $conversation->second_user_uniq_id,
                                'bitsky_ip' => empty($_POST['fromStranger']) ? $bitsky_ip : null
                            ]
                        ), true);

                        $conversation->user = $user['user'];
                    } else
                    {
                        $conversation->user = User::where('uniq_id', $conversation->second_user_uniq_id)->first(['id', 'firstname', 'lastname', 'avatar']);
                    }

                    unset($conversation->first_user_uniq_id);
                    unset($conversation->second_user_uniq_id);
                }

                if($_POST['uniq_id'] == $conversation->second_user_uniq_id)
                {
                    if(!empty($conversation->link_id))
                    {
                        $linkController = new Link();
                        $_POST['link_id'] = $conversation->link_id;
                        $link = json_decode($linkController->getLinkById(), true);
                        $link = $link['link'];

                        $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                        $bitsky_ip = $bitsky_ip['data'];

                        $user = json_decode($this->callAPI(
                            'POST',
                            'http://localhost/get_user_by_uniq_id',
                            [
                                'uniq_id' => $_POST['uniq_id'],
                                'token' => $_POST['token'],
                                'user_uniq_id' => $conversation->first_user_uniq_id,
                                'bitsky_ip' => empty($_POST['fromStranger']) ? $bitsky_ip : null
                            ]
                        ), true);

                        $conversation->user = $user['user'];
                    } else
                    {
                        $conversation->user = User::where('uniq_id', $conversation->first_user_uniq_id)->first(['id', 'firstname', 'lastname', 'avatar']);
                    }

                    unset($conversation->first_user_uniq_id);
                    unset($conversation->second_user_uniq_id);
                }

                $conversation->lastMessage = Message::where('conversation_id', $conversation->id)->orderBy('created_at', 'desc')->first();

                if(!empty($conversation->lastMessage) && $conversation->lastMessage->receiver_uniq_id == $_POST['uniq_id'] && $conversation->lastMessage->receiver_read == 0)
                {
                    $conversation->unread = true;
                }
            }

            return json_encode(['success' => true, 'conversations' => $conversations]);
        } else
        {
            LogManager::store('[POST] Tentative récupération des conversations avec un token invalide (ID utilisateur: '.$_POST['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getConversations()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $conversations = [];

            $localConversations = json_decode($this->getLocalConversations(), true);

            if($localConversations['success'])
            {
                $conversations = $localConversations['conversations'];
            }

            $linkedDevices = \Model\Link::all();

            if(count($linkedDevices) == 0 || empty($linkedDevices)) {
                return json_encode(['success' => true, 'conversations' => $conversations]);
            }

            $links = $this->callAPI(
                'POST',
                'https://bitsky.be/getActiveLinks',
                [
                    'bitsky_key' => getenv('LINKING_KEY')
                ]
            );

            $links = json_decode($links, true);

            if($links['success'])
            {
                foreach($links['data'] as $link)
                {
                    $linkConversations = $this->callAPI(
                        'POST',
                        'http://' . $link['foreign_ip'] . '/get_local_conversations',
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'fromStranger' => true
                        ]
                    );

                    $linkConversations = json_decode($linkConversations, true);

                    if($linkConversations['success'])
                    {
                        foreach($linkConversations['conversations'] as $linkConversation)
                        {
                            $linkConversation['from_stranger'] = $link['foreign_ip'];
                            array_push($conversations, $linkConversation);
                        }
                    } else
                    {
                        return json_encode(['success' => false, 'linkedConversationErrorResponse' => json_decode($linkConversations)]);
                    }
                }
                return json_encode(['success' => true, 'conversations' => $conversations]);
            }
        } else
        {
            LogManager::store('[POST] Tentative récupération des conversations avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getConversationByID()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            $id = htmlspecialchars($_POST['conversation_id']);
            $conversation = Conversation::where('id', $id)->first();

            if(!empty($conversation))
            {
                return json_encode(['success' => true, 'conversation' => $conversation]);
            } else
            {
                return $this->forbidden('conversationNotFound');
            }
        } else
        {
            LogManager::store('[POST] Tentative récupération d\'une conversation avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getConversation()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            $actualUser = User::where('uniq_id', $check['uniq_id'])->first();
            $id = htmlspecialchars($_POST['conversation_id']);
            $link_id = htmlspecialchars($_POST['link_id']);

            $conversation = Conversation::where('id', $id)->first();
            $bitsky_ip = null;

            if(!empty($link_id))
            {
                $linkController = new Link();
                $link = json_decode($linkController->getLinkById(), true);
                $link = $link['link'];

                $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                $bitsky_ip = $bitsky_ip['data'];
            }

            if(empty($conversation) && $link_id)
            {
                $url = $bitsky_ip . '/get_conversation_by_id';

                $request = $this->callAPI(
                    'POST',
                    $url,
                    [
                        'uniq_id' => $check['uniq_id'],
                        'token' => $check['token'],
                        'conversation_id' => $id
                    ]
                );

                $request = json_decode($request, true);

                if($request['success'])
                {
                    $conversation = $request['conversation'];
                } else
                {
                    return $this->forbidden('conversationNotFoundInLinks');
                }
            }

            $_POST['conversation'] = $conversation;

            if($check['uniq_id'] == $conversation['first_user_uniq_id'] || $check['uniq_id'] == $conversation['second_user_uniq_id'] || $actualUser->rank == 2)
            {
                if($check['uniq_id'] == $conversation['first_user_uniq_id'])
                {
                    $user = User::where('uniq_id', $conversation['second_user_uniq_id'])->first(['id', 'uniq_id', 'firstname', 'lastname', 'avatar']);

                    if(!empty($user))
                    {
                        $conversation['user'] = $user;
                    }
                }

                if($check['uniq_id'] == $conversation['second_user_uniq_id'])
                {
                    $user = User::where('uniq_id', $conversation['first_user_uniq_id'])->first(['id', 'uniq_id', 'firstname', 'lastname', 'avatar']);

                    if(!empty($user))
                    {
                        $conversation['user'] = $user;
                    }
                }

                if(empty($conversation['user']) && !empty($link_id))
                {
                    if($check['uniq_id'] == $conversation['first_user_uniq_id'])
                    {
                        $user = json_decode($this->callAPI(
                            'POST',
                            'http://localhost/get_user_by_uniq_id',
                            [
                                'uniq_id' => $_POST['uniq_id'],
                                'token' => $_POST['token'],
                                'user_uniq_id' => $conversation['second_user_uniq_id'],
                                'bitsky_ip' => $bitsky_ip
                            ]
                        ), true);

                        $conversation['user'] = $user['user'];
                    }

                    if($check['uniq_id'] == $conversation['second_user_uniq_id'])
                    {
                        $user = json_decode($this->callAPI(
                            'POST',
                            'http://localhost/get_user_by_uniq_id',
                            [
                                'uniq_id' => $_POST['uniq_id'],
                                'token' => $_POST['token'],
                                'user_uniq_id' => $conversation['first_user_uniq_id'],
                                'bitsky_ip' => $bitsky_ip
                            ]
                        ), true);

                        $conversation['user'] = $user['user'];
                    }
                }

                $messages = $this->getMessages();

                foreach($messages as $message)
                {
                    if($message['receiver_uniq_id'] == $check['uniq_id'])
                    {
                        if($_POST['conversation']['first_user_uniq_id'] == $_POST['uniq_id'] || empty($bitsky_ip))
                        {
                            $messageToUpdate = Message::where('id', $message['id'])->first();
                            $messageToUpdate->receiver_read = 1;
                            $messageToUpdate->save();
                        } else
                        {
                            $this->callAPI(
                                'POST',
                                'http://' . $bitsky_ip . '/read_message',
                                [
                                    'uniq_id' => $_POST['uniq_id'],
                                    'token' => $_POST['token'],
                                    'message_id' => $message['id'],
                                ]
                            );
                        }
                    }
                }

                $conversation['messages'] = $this->getMessages($conversation);

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

    public function localReadMessage()
    {
        $check = $this->checkUserToken();
        $id = $_POST['message_id'];
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            $messageToUpdate = Message::where('id', $id)->first();
            $messageToUpdate->receiver_read = 1;
            $messageToUpdate->save();
        }
    }

    public function localGetMessages()
    {
        $check = $this->checkUserToken();
        $conversation = (!empty($_POST['conversation']) ? json_decode($_POST['conversation'], true) : null);
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            return Message::where('conversation_id', $conversation['id'])->orderBy('created_at', 'asc')->get();
        } else
        {
            return false;
        }
    }

    public function getMessages()
    {
        $check = $this->checkUserToken();
        $conversation = (!empty($_POST['conversation']) ? $_POST['conversation'] : null);

        if(!empty($check) && !empty($conversation))
        {
            $isStranger = ($check['uniq_id'] == $conversation['second_user_uniq_id']) && !empty($conversation['link_id']);

            if(!$isStranger)
            {
                return Message::where('conversation_id', $conversation['id'])->orderBy('created_at', 'asc')->get();
            }

            $_POST['link_id'] = $conversation['link_id'];

            $linkController = new Link();
            $link = json_decode($linkController->getLinkById(), true);
            $link = $link['link'];

            $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
            $bitsky_ip = $bitsky_ip['data'];

            return json_decode($this->callAPI(
                'POST',
                'http://' . $bitsky_ip . '/get_messages',
                [
                    'uniq_id' => $_POST['uniq_id'],
                    'token' => $_POST['token'],
                    'conversation' => json_encode($conversation),
                ]
            ), true);
        } else
        {
            return false;
        }
    }

    public function localSendMessage()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            if(!empty($_POST['conversation_id']) && !empty($_POST['sender_uniq_id']) && !empty($_POST['receiver_uniq_id']) && !empty($_POST['content']))
            {
                $message = Message::create([
                    'conversation_id' => htmlspecialchars($_POST['conversation_id']),
                    'sender_uniq_id' => htmlspecialchars($_POST['sender_uniq_id']),
                    'receiver_uniq_id' => htmlspecialchars($_POST['receiver_uniq_id']),
                    'content' => htmlspecialchars($_POST['content']),
                ]);

                return json_encode(['success' => true, 'message' => $message]);
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

    public function sendMessage()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if(!empty($_POST['conversation_id']) && !empty($_POST['receiver_uniq_id']) && !empty($_POST['content']))
            {
                $conversationId = htmlspecialchars($_POST['conversation_id']);
                $receiverUniqId = htmlspecialchars($_POST['receiver_uniq_id']);
                $link_id = !empty($_POST['conversation_link_id']) ? htmlspecialchars($_POST['conversation_link_id']) : null;
                $content = htmlspecialchars($_POST['content']);
                $conversation = Conversation::find($conversationId);
                $bitsky_ip = null;

                if(empty($conversation) && !empty($link_id))
                {
                    $_POST['link_id'] = $link_id;
                    $linkController = new Link();
                    $link = json_decode($linkController->getLinkById(), true);
                    $link = $link['link'];

                    $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                    $bitsky_ip = $bitsky_ip['data'];

                    $url = $bitsky_ip . '/get_conversation_by_id';

                    $request = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'conversation_id' => $conversationId,
                        ]
                    );

                    $request = json_decode($request, true);
                    $conversation = $request['conversation'];
                }

                if(!empty($conversation))
                {
                    if(empty($bitsky_ip))
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
                        $url = $bitsky_ip . '/local_send_message';

                        return $this->callAPI(
                            'POST',
                            $url,
                            [
                                'uniq_id' => $check['uniq_id'],
                                'token' => $check['token'],
                                'conversation_id' => $conversationId,
                                'sender_uniq_id' => htmlspecialchars($_POST['uniq_id']),
                                'receiver_uniq_id' => htmlspecialchars($_POST['receiver_uniq_id']),
                                'content' => htmlspecialchars($_POST['content']),
                            ]
                        );
                    }
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

    public function localCountUnreadMessages()
    {
        $check = $this->checkUserToken();
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($check) || $authorizedForeign)
        {
            $count = 0;
            $uniq_id = $_POST['uniq_id'];

            $conversations = Conversation::where(function ($query) use ($uniq_id) {
                $query->where('first_user_uniq_id', $uniq_id)
                    ->orWhere('second_user_uniq_id', $uniq_id);
            })->get();

            foreach($conversations as $conversation)
            {
                $count += Message::where('conversation_id', $conversation->id)
                    ->where('receiver_uniq_id', $uniq_id)
                    ->where('receiver_read', 0)->count();
            }

            return $count;
        } else
        {
            return 0;
        }
    }

    public function countUnreadMessages()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $count = $this->localCountUnreadMessages();

            $linkedDevices = \Model\Link::all();

            if(count($linkedDevices) == 0 || empty($linkedDevices)) {
                return json_encode(['success' => true, 'count' => $count]);
            }

            $links = $this->callAPI(
                'POST',
                'https://bitsky.be/getActiveLinks',
                [
                    'bitsky_key' => getenv('LINKING_KEY')
                ]
            );

            $links = json_decode($links, true);

            if($links['success']) {
                foreach ($links['data'] as $link) {
                    $count += $this->callAPI(
                        'POST',
                        'http://' . $link['foreign_ip'] . '/local_count_unread_messages',
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'fromStranger' => true
                        ]
                    );
                }
            }

            return json_encode(['success' => true, 'count' => $count]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération du nombre de message avec un token invalide (ID utilisateur: '.$check['uniq_id'].')', 2);
            return $this->forbidden('invalidToken');
        }
    }
}