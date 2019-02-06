<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Kernel\LogManager;
    use \Controller\Auth;
    use \Model\Post as PostModel;
    use \Model\PostFavorite as PostFavoriteModel;
    use \Model\Tag as TagModel;
    use \Model\User as UserModel;
    use \Model\PostComment as PostCommentModel;
    use \Model\PostCommentFavorite as PostCommentFavoriteModel;


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

                        $comments = PostCommentModel::where('post_id', $post_id);

                        foreach($comments->get() as $comment)
                        {
                            $favorites = PostCommentFavoriteModel::where('post_comment_id', $comment->id)->delete();
                        }

                        $comments->delete();

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

        public function get()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                   if(!empty($_POST['post_id']))
                   {
                        $id = htmlspecialchars($_POST['post_id']);

                        $post = PostModel::where('id', $id)->first();

                        if($post != null)
                        {
                            $post->tag = TagModel::where('id', $post->tag_id)->first()->name;
                            $post->owner = UserModel::where('uniq_id', $post->owner_uniq_id)->first(['firstname', 'lastname', 'rank', 'avatar']);
                            unset($post->owner_uniq_id);
                            return json_encode(['success' => true, 'post' => $post]);
                        }else
                        {
                            return $this->forbidden('notFound');
                        }
                   }else
                   {
                       return $this->forbidden();
                   }                 
                }else
                {
                    LogManager::store('[POST] Tentative de récupération d\'un post avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
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
                    $tagName = (!empty($_POST['trend'])) ? htmlspecialchars($_POST['trend']) : null;

                    $posts = null;

                    if(empty($tagName))
                    {
                        $posts = PostModel::orderBy('created_at', 'desc')->get();
                    }else
                    {
                        $tag = TagModel::where('name', $tagName)->first();

                        $posts = PostModel::where('tag_id', $tag->id)->orderBy('created_at', 'desc')->get();
                    }

                    foreach($posts as $post)
                    {
                        $post->tag = TagModel::where('id', $post->tag_id)->first()->name;
                        $post->owner = UserModel::where('uniq_id', $post->owner_uniq_id)->first(['firstname', 'lastname', 'rank', 'avatar']);
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

        public function getScore()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                   if(!empty($_POST['post_id']))
                   {
                        $id = htmlspecialchars($_POST['post_id']);

                        $post = PostModel::where('id', $id)->first();

                        if($post != null)
                        {
                            $score = 16;

                            $favorites = PostFavoriteModel::where('post_id', $id)->get();

                            foreach($favorites as $favorite)
                            {
                                $score += 8;
                            }

                            $comments = PostCommentModel::where('post_id', $id)->get();

                            foreach($comments as $comment)
                            {
                                $score += 4;

                                $commentFavorites = PostCommentFavoriteModel::where('post_comment_id', $comment->id)->get();

                                foreach($commentFavorites as $commentFavorites)
                                {
                                    $score += 2;
                                }
                            }

                            return json_encode(['success' => true, 'score' => $score]);
                        }else
                        {
                            return $this->forbidden('notFound');
                        }
                   }else
                   {
                       return $this->forbidden();
                   }                 
                }else
                {
                    LogManager::store('[POST] Tentative de récupération du score d\'un post avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
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
                    $tags = TagModel::orderBy('uses', 'desc')->get();

                    // Score definition
                    foreach($tags as $tag)
                    {
                        $tag->score = 0;

                        $posts = PostModel::where('tag_id',$tag->id)->get();

                        foreach($posts as $post)
                        {
                            $tag->score += 16;

                            $favorites = PostFavoriteModel::where('post_id', $post->id)->get();

                            foreach($favorites as $favorite)
                            {
                                $tag->score += 8;
                            }

                            $comments = PostCommentModel::where('post_id', $post->id)->get();

                            foreach($comments as $comment)
                            {
                                $tag->score += 4;

                                $commentFavorites = PostCommentFavoriteModel::where('post_comment_id', $comment->id)->get();

                                foreach($commentFavorites as $commentFavorite)
                                {
                                    $tag->score += 2;
                                }
                            }
                        }
                    }

                    // Score sorting
                    $scores = [];

                    foreach($tags as $key => $row)
                    {
                        $scores[$key] = $row['score'];
                    }

                    $tags = json_decode(json_encode($tags), true);

                    array_multisort($scores, SORT_DESC, $tags);

                    // Get 3 best tags
                    $tags = array_slice($tags, 0, 3);
                    $tags = json_decode(json_encode($tags));

                    
                    $trends = [];

                    foreach($tags as $tag)
                    {
                        $post = PostModel::where('tag_id',$tag->id)->orderBy('id', 'desc')->first();
                        $user = UserModel::where('uniq_id', $post->owner_uniq_id)->first();

                        array_push($trends, [
                            'name' => $tag->name,
                            'score' => $tag->score,
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

        public function getAllComments()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $comments = PostCommentModel::orderBy('created_at', 'asc')->get();

                    foreach($comments as $comment)
                    {
                        $comment->owner = UserModel::where('uniq_id', $comment->owner_id)->first(['firstname', 'lastname', 'avatar']);
                        unset($comment->owner_id);
                    }
                    
                    return json_encode(['success' => true, 'comments' => $comments]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération des commentaires avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getComments()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    if(!empty($_POST['post_id']))
                    {
                        $post_id = htmlspecialchars($_POST['post_id']);
                        $comments = PostCommentModel::where('post_id', $post_id)->get();

                        foreach($comments as $comment)
                        {
                            $comment->owner = UserModel::where('uniq_id', $comment->owner_id)->first(['firstname', 'lastname', 'rank', 'avatar']);
                        }
                        
                        return json_encode(['success' => true, 'comments' => $comments]);
                    }else
                    {
                        return $this->forbidden('noPostId');    
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de récupération des commentaires avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function addCommentFavorite()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_comment_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_comment_id = htmlspecialchars($_POST['post_comment_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $comment = PostCommentModel::find($post_comment_id);
                    $comment->favorites = $comment->favorites + 1;
                    $comment->save();

                    PostCommentFavoriteModel::create([
                        'post_comment_id' => $post_comment_id,
                        'user_uniq_id' => $uniq_id
                    ]);

                    return json_encode(['success' => true]);
                }else
                {
                    LogManager::store('[POST] Tentative d\'ajout d\'un commentaire en favoris avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function removeCommentFavorite()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_comment_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_comment_id = htmlspecialchars($_POST['post_comment_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $comment = PostCommentModel::find($post_comment_id);
                    $comment->favorites = $comment->favorites - 1;
                    $comment->save();

                    PostCommentFavoriteModel::where('post_comment_id', $post_comment_id)->where('user_uniq_id', $uniq_id)->delete();

                    return json_encode(['success' => true]);
                }else
                {
                    LogManager::store('[POST] Tentative d\'ajout d\'un commentaire en favoris avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getCommentFavoriteOfUser()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_comment_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $post_comment_id = htmlspecialchars($_POST['post_comment_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $commentFavorite = PostCommentFavoriteModel::where('post_comment_id',$post_comment_id)
                    ->where('user_uniq_id', $uniq_id)->first();

                    return json_encode(['success' => true, 'favorite' => $commentFavorite != null]);
                }else
                {
                    LogManager::store('[POST] Tentative de récupération d\'un favoris de commentaire avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getCommentsCount() 
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    if(!empty($_POST['post_id']))
                    {
                        $post_id = htmlspecialchars($_POST['post_id']);
                        $commentsCount = PostCommentModel::where('post_id', $post_id)->count();
                        
                        return json_encode(['success' => true, 'commentsCount' => $commentsCount]);
                    }else
                    {
                        return $this->forbidden('noPostId');    
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de récupération du nombre de commentaires avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function getBestComments()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    if(!empty($_POST['post_id']))
                    {
                        $post_id = htmlspecialchars($_POST['post_id']);
                        $comments = PostCommentModel::where('post_id', $post_id)->orderBy('favorites', 'desc')->take(3)->get();

                        foreach($comments as $comment)
                        {
                            $comment->owner = UserModel::where('uniq_id', $comment->owner_id)->first(['firstname', 'lastname', 'avatar']);
                            unset($comment->owner_id);
                        }
                        
                        return json_encode(['success' => true, 'comments' => $comments]);
                    }else
                    {
                        return $this->forbidden('noPostId');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de récupération des meilleurs commentaires avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function addComment()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    if(!empty($_POST['post_id']) && !empty($_POST['content']))
                    {
                        $post_id = htmlspecialchars($_POST['post_id']);
                        $content = htmlspecialchars(trim($_POST['content']));

                        $post = PostModel::where('id', $post_id)->first();

                        if($post != null)
                        {
                            if(strlen($content) > 0)
                            {
                                $comment = PostCommentModel::create([
                                    'owner_id' => $uniq_id,
                                    'post_id' => $post_id,
                                    'content' => $content
                                ]);
        
                                if($comment != null)
                                {
                                    $post->comments = $post->comments + 1;
                                    $post->save();

                                    $comment->owner = UserModel::where('uniq_id', $uniq_id)->first(['firstname', 'lastname', 'avatar']);
                                    unset($comment->owner_id);

                                    return json_encode(['success' => true, 'comment' => $comment]);
                                }else
                                {
                                    return $this->forbidden();
                                }
                            }else
                            {
                                return $this->forbidden('contentEmpty');
                            }
                        }else
                        {
                            return $this->forbidden('postNotFound');
                        }
                    }else
                    {
                        return $this->forbidden('notFilled');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de création d\'un commentaire avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function removeComment()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['comment_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $comment_id = htmlspecialchars($_POST['comment_id']);

                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if($verify->success)
                {
                    $user = UserModel::where('uniq_id', $uniq_id)->first();
                    $comment = PostCommentModel::where('id', $comment_id)->first();
                    if($user->rank == 2 || $user->uniq_id == $comment->owner_id)
                    {
                        PostCommentFavoriteModel::where('post_comment_id', $comment_id)->delete();
                        
                        $post = PostModel::where('id', $comment->post_id)->first();
                        
                        if($comment != null)
                        {
                            $comment->delete();

                            if($post != null) 
                            {
                                $post->comments = $post->comments - 1;
                                $post->save();
                                return json_encode(['success' => true]);
                            }else
                            {
                                return $this->forbidden();
                            }
                        }else
                        {
                            return $this->forbidden();
                        }

                        return json_encode(['success' => true]);
                    }else
                    {
                        LogManager::store('[POST] Tentative de suppression d\'un commentaire sans autorisation (ID utilisateur: '.$uniq_id.')', 2);
                        return $this->forbidden();
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de suppression d\'un commentaire avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                    return $this->forbidden('invalidToken');
                }
            }
        }
    }