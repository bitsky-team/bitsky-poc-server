<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Kernel\LogManager;
    use \Controller\Auth;
    use \Model\Post as PostModel;
    use \Model\PostFavorite as PostFavoriteModel;
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
                        $tag->uses = $tag->uses + 1;
                        $tag->save();
                    }else
                    {
                        $tag = TagModel::create(['name' => ucfirst($tag), 'uses' => 1]);
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
                        PostFavoriteModel::where('post_id', $post_id)->delete();
                        
                        $tag = TagModel::where('id', $post->tag_id)->first();
                        
                        if($tag->uses > 1) 
                        {
                            $tag->uses = $tag->uses - 1;
                            $tag->save();
                        }else
                        {
                            $tag->delete();
                        }

                        if($post != null) $post->delete();
                        return json_encode(['success' => true]);
                    }else
                    {
                        LogManager::store('[POST] Tentative de suppression d\'un article sans autorisation (ID utilisateur: '.$uniq_id.')', 2);
                        return $this->forbidden();
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de suppression d\'un article avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
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

        public function addFavorite()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_id = htmlspecialchars($_POST['post_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $post = PostModel::find($post_id);
                    $post->favorites = $post->favorites + 1;
                    $post->save();

                    PostFavoriteModel::create([
                        'post_id' => $post_id,
                        'user_uniq_id' => $uniq_id
                    ]);
                    return json_encode(['success' => true]);
                }else
                {
                    LogManager::store('[POST] Tentative d\'ajout d\'un post en favoris avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function removeFavorite()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_id = htmlspecialchars($_POST['post_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $post = PostModel::find($post_id);
                    $post->favorites = $post->favorites - 1;
                    $post->save();

                    $postFavorite = PostFavoriteModel::where('post_id',$post_id)->where('user_uniq_id', $uniq_id)->first();
                    $postFavorite->delete();
                    
                    return json_encode(['success' => true]);
                }else
                {
                    LogManager::store('[POST] Tentative de retrait d\'un post en favoris avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getFavoriteOfUser()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_id = htmlspecialchars($_POST['post_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $postFavorite = PostFavoriteModel::where('post_id',$post_id)
                    ->where('user_uniq_id', $uniq_id)->first();

                    return json_encode(['success' => true, 'favorite' => $postFavorite != null]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération d\'un favoris de post avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getTrends()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $tags = TagModel::orderBy('uses', 'desc')->take(3)->get();
                    $trends = [];

                    foreach($tags as $tag)
                    {
                        $post = PostModel::where('tag_id',$tag->id)->orderBy('id', 'desc')->first();
                        $user = UserModel::where('uniq_id', $post->owner_uniq_id)->first();

                        array_push($trends, [
                            'name' => $tag->name,
                            'post' => [
                                'content' => $post->content,
                                'owner'   => $user->firstname . ' ' . $user->lastname
                            ]
                        ]);
                    }

                    return json_encode(['success' => true, 'trends' => $trends]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération d\'un favoris de post avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }
    }