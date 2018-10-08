<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Kernel\LogManager;
    use \Controller\Auth;
    use \Model\Post as PostModel;
    use \Model\Tag as TagModel;
    use \Model\User as UserModel;

    class Post extends Controller
    {
        public function __construct()
        {
            $this->authService = new Auth();
        }

        public function store()
        {
            if(!empty($_POST['token']) && !empty($_POST['owner_uniq_id']) && !empty($_POST['content']) && !empty($_POST['tag']))
            {
                $token = htmlspecialchars($_POST['token']);
                $owner_uniq_id = htmlspecialchars($_POST['owner_uniq_id']);
                $content = htmlspecialchars($_POST['content']);
                $tag = htmlspecialchars(ucfirst($_POST['tag']));

                $verify = json_decode($this->authService->verify($token, $owner_uniq_id));

                if($verify->success)
                {
                    $doesTagExists = TagModel::where('name', $tag)->count() > 0;
                    
                    if($doesTagExists)
                    {
                        $tag = TagModel::where('name', $tag)->first();
                    }else
                    {
                        $tag = TagModel::create(['name' => $tag]);
                    }

                    $post = PostModel::create([
                        'owner_uniq_id' => $owner_uniq_id,
                        'content'       => $content,
                        'tag_id'        => $tag->id
                    ]);

                    $user = UserModel::where('uniq_id', $owner_uniq_id)->first();

                    return json_encode(['success' => true, 'postId' => $post->id, 'ownerRank' => $user->rank]);
                }else
                {
                    LogManager::store('[POST] Tentative de création de post avec un token invalide (ID utilisateur: '.$owner_uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('emptyInput');
            }
        }

        public function remove()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_id = htmlspecialchars($_POST['post_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $user = UserModel::where('uniq_id', $uniq_id)->first();
                    $post = PostModel::where('id', $post_id)->first();

                    if($user->rank == 2 || $user->uniq_id == $post->owner_uniq_id)
                    {
                        $post->delete();
                        return json_encode(['success' => true]);
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de suppression d\'un article avec un token invalide (ID utilisateur: '.$owner_uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }
        }

        public function getAll() 
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $posts = PostModel::orderBy('created_at', 'desc')->get();

                    foreach($posts as $post)
                    {
                        $post->tag = TagModel::where('id', $post->tag_id)->first()->name;
                        $post->owner = UserModel::where('uniq_id', $post->owner_uniq_id)->first(['firstname', 'lastname', 'rank']);
                        unset($post->owner_uniq_id);
                    }

                    return json_encode(['success' => true, 'posts' => $posts]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération des posts avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }
    }