<?php

    namespace Controller;

    use \Controller\Auth;
    use \Kernel\LogManager;
    use \Model\User as UserModel;
    use \Model\Link as LinkModel;

    class Link extends Controller
    {
        public function __construct()
        {
            $this->authService = new Auth();
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
                        LogManager::store('[POST] Tentative de récupération de la clé de liaison avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                        return $this->forbidden('forbidden');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de récupération de la clé de liaison avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function createLink()
        {
            if(
                !empty($_POST['token']) &&
                !empty($_POST['uniq_id']) &&
                !empty($_POST['bitsky_name']) &&
                !empty($_POST['bitsky_key']) &&
                isset($_POST['link_state'])
            )
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $name = htmlspecialchars($_POST['bitsky_name']);
                $key = htmlspecialchars($_POST['bitsky_key']);
                $active = htmlspecialchars($_POST['link_state']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $user = UserModel::where('uniq_id', $uniq_id)->first();

                    if($user['rank'] == 2)
                    {
                        $existingLink = LinkModel::where('name', $name)->orWhere('bitsky_key', $key)->first();

                        if(empty($existingLink))
                        {
                            $link = LinkModel::create([
                                'name' => $name,
                                'bitsky_key' => $key,
                                'active' => $active
                            ]);

                            return json_encode(['success' => true, 'link' => $link]);
                        } else
                        {
                            return $this->forbidden('alreadyExists');
                        }
                    }else
                    {
                        LogManager::store('[POST] Tentative de création de liaison avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                        return $this->forbidden('forbidden');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de création de liaison avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }
    }