<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Model\Post as PostModel;
    use \Controller\Auth;
    use \Controller\Log;

    class Post extends Controller
    {
        public function __construct()
        {
            $this->authService = new Auth();
        }

        public function store()
        {
            if(!empty($_POST['token']) && !empty($_POST['owner_uniq_id']) && !empty($_POST['content']) && !empty($_POST['tag_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $owner_uniq_id = htmlspecialchars($_POST['owner_uniq_id']);
                $content = htmlspecialchars($_POST['content']);
                $tag_id = htmlspecialchars($_POST['tag_id']);

                $verify = json_decode($this->authService->verify($token, $owner_uniq_id));

                if($verify->success)
                {
                    PostModel::create([
                        'owner_uniq_id' => $owner_uniq_id,
                        'content'       => $content,
                        'tag_id'        => $tag_id
                    ]);

                    return json_encode(['success' => true]);
                }else
                {
                    Log::store('[POST] Tentative de création de post avec un token invalide (ID utilisateur: '.$owner_uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }
        }
    }