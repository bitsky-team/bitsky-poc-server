<?php

    namespace Controller;

    use \Kernel\LogManager;
    use \Kernel\RemoteAddress;
    use \Model\User as UserModel;
    use \Model\Link as LinkModel;

    /**
     * @property RemoteAddress remoteAddress
     * @property \Controller\Auth authService
     */
    class Link extends Controller
    {
        public function __construct()
        {
            $this->authService = new Auth();
            $this->remoteAddress = new RemoteAddress();
        }

        public function getKeyOfIp($ip)
        {
            return $this->callAPI(
                'POST',
                'https://bitsky.be/getKey',
                [
                    'bitsky_ip' => $ip
                ]
            );
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

        public function activeLink()
        {
            if(!empty($_POST['bitsky_key']))
            {
                $key = htmlspecialchars($_POST['bitsky_key']);

                $link = LinkModel::where('bitsky_key', $key)->first();

                if(!empty($link))
                {
                    if($this->remoteAddress->getIpAddress() == '149.91.80.202')
                    {
                        $link->active = 1;
                        $link->save();

                        return json_encode(['success' => true, 'link' => $link]);
                    }else
                    {
                        return $this->forbidden();
                    }
                } else
                {
                    return $this->forbidden('doesntExist');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getActiveLinks()
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
                        $links = LinkModel::where('active', 1)->get();
                        return json_encode(['success' => true, 'key' => $links]);
                    }else
                    {
                        LogManager::store('[POST] Tentative de récupération des liaisons avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                        return $this->forbidden('forbidden');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de récupération des liaisons avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getLink()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['bitsky_key']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $key = htmlspecialchars($_POST['bitsky_key']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $link = LinkModel::where('bitsky_key', $key)->first();
                    return json_encode(['success' => true, 'link' => $link]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération d\'une liaison avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function deleteLink()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['bitsky_key']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $key = htmlspecialchars($_POST['bitsky_key']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $user = UserModel::where('uniq_id', $uniq_id)->first();

                    if($user['rank'] == 2)
                    {
                        $link = LinkModel::where('bitsky_key', $key)->first();
                        $link->delete();
                        return json_encode(['success' => true]);
                    }else
                    {
                        LogManager::store('[POST] Tentative de suppression d\'une liaison avec un rang trop bas (ID utilisateur: '.$uniq_id.')', 2);
                        return $this->forbidden('forbidden');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de suppression d\'une liaison avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function deleteLinkIntermediary()
        {
            if(!empty($_POST['bitsky_key']) && $this->remoteAddress->getIpAddress() == '149.91.80.202')
            {
                $key = htmlspecialchars($_POST['bitsky_key']);

                $link = LinkModel::where('bitsky_key', $key)->first();
                $link->delete();

                return json_encode(['success' => true]);
            }else
            {
                return $this->forbidden('notAuthorized');
            }
        }
    }