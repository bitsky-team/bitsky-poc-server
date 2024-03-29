<?php

namespace Controller;

use \Kernel\JWT;
use \Kernel\LogManager;
use \Controller\Auth;
use \Kernel\RemoteAddress;
use Model\Notification;
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
            $picture = !empty($_POST['picture']) ? htmlspecialchars($_POST['picture']) : null;

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
                    'tag_id'        => $tag->id,
                    'picture'       => $picture
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
        } else
        {
            return $this->forbidden('noInformations');
        }
    }

    public function getLocal()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                if(!empty($_POST['post_id']))
                {
                    $id = htmlspecialchars($_POST['post_id']);

                    $post = PostModel::where('id', $id)->first();

                    if($post != null)
                    {
                        $post->tag = TagModel::where('id', $post->tag_id)->first()->name;
                        $post->owner = UserModel::where('uniq_id', $post->owner_uniq_id)->first(['id', 'firstname', 'lastname', 'rank', 'avatar']);
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

    public function get()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocal();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/get_localpost';

                    $favorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    return $favorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération d\'un post sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération d\'un post avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalPosts()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if((!empty($_POST['token']) && !empty($_POST['uniq_id'])) || $authorizedForeign)
        {
            $token = !empty($_POST['token']) ? htmlspecialchars($_POST['token']) : false;
            $uniq_id = !empty($_POST['uniq_id']) ? htmlspecialchars($_POST['uniq_id']) : 'linkedDevice';

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                $tagName = (!empty($_POST['trend'])) ? htmlspecialchars($_POST['trend']) : null;

                $posts = null;

                if(empty($tagName))
                {
                    $posts = PostModel::orderBy('created_at', 'desc')->get();
                }else
                {
                    $tag = TagModel::where('name', $tagName)->first();

                    if(!empty($tag))
                    {
                        $posts = PostModel::where('tag_id', $tag->id)->orderBy('created_at', 'desc')->get();
                    } else
                    {
                        $posts = [];
                    }
                }

                foreach($posts as $post)
                {
                    $post->tag = TagModel::where('id', $post->tag_id)->first()->name;
                    $post->owner = UserModel::where('uniq_id', $post->owner_uniq_id)->first(['id', 'firstname', 'lastname', 'rank', 'avatar']);
                    unset($post->owner_uniq_id);
                }

                return json_encode(['success' => true, 'posts' => $posts]);
            }else
            {
                LogManager::store('[POST] Tentative de récupération des posts avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
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
                $posts = [];

                $localPosts = json_decode($this->getLocalPosts(), true);

                if($localPosts['success'])
                {
                    $posts = $localPosts['posts'];
                }

                $linkedDevices = \Model\Link::all();

                if(count($linkedDevices) == 0 || empty($linkedDevices)) {
                    return json_encode(['success' => true, 'posts' => $posts]);
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
                        $linkPosts = $this->callAPI(
                            'POST',
                            'http://' . $link['foreign_ip'] . '/get_localposts',
                            [
                                'trend' => !empty($_POST['trend']) ? htmlspecialchars($_POST['trend']) : null
                            ]
                        );

                        $linkPosts = json_decode($linkPosts, true);

                        if($linkPosts['success'])
                        {
                            foreach($linkPosts['posts'] as $linkPost)
                            {
                                $linkPost['from_stranger'] = $link['foreign_ip'];
                                array_push($posts, $linkPost);
                            }
                        }
                    }
                    return json_encode(['success' => true, 'posts' => $posts]);
                }
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

    public function getAllOfUser()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if((!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['user_id'])) || ($authorizedForeign && !empty($_POST['user_id'])))
        {
            $token = !empty($_POST['token']) ? htmlspecialchars($_POST['token']) : false;
            $uniq_id = !empty($_POST['uniq_id']) ? htmlspecialchars($_POST['uniq_id']) : 'linkedDevice';
            $user_id = htmlspecialchars($_POST['user_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                $user = UserModel::where('id', $user_id)->first(['id', 'uniq_id', 'firstname', 'lastname', 'rank', 'avatar']);

                $posts = null;
                $posts = PostModel::where('owner_uniq_id', $user->uniq_id)->orderBy('created_at', 'desc')->get();

                foreach($posts as $post)
                {
                    $post->tag = TagModel::where('id', $post->tag_id)->first()->name;

                    unset($user['uniq_id']);
                    $post->owner = $user;

                    if($authorizedForeign && !empty($_POST['bitsky_ip'])) {
                        $post->fromStranger = $_POST['bitsky_ip'];
                    }

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

    public function getAllOfStrangerUser()
    {
        if(!empty($_POST['uniq_id']) && !empty($_POST['bitsky_ip']) && !empty($_POST['user_id']))
        {
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $bitsky_ip = htmlspecialchars($_POST['bitsky_ip']);
            $user_id = htmlspecialchars($_POST['user_id']);

            $links = $this->callAPI(
                'POST',
                'https://bitsky.be/getActiveLinks',
                [
                    'bitsky_key' => getenv('LINKING_KEY')
                ]
            );

            $links = json_decode($links, true);
            $correctStranger = false;

            if($links['success'])
            {
                foreach ($links['data'] as $link)
                {
                    if($bitsky_ip == $link['foreign_ip'])
                    {
                        $correctStranger = true;
                    }
                }

                if($correctStranger)
                {
                    $response = $this->callAPI(
                        'POST',
                        'http://' . $link['foreign_ip'] . '/get_allpostsofuser',
                        [
                            'user_id' => $user_id,
                            'bitsky_ip' => $link['foreign_ip']
                        ]
                    );

                    $response = json_decode($response, true);

                    if($response['success'])
                    {
                        return json_encode(['success' => true, 'posts' => $response['posts']]);
                    } else
                    {
                        $response['stranger'] = true;
                        return json_encode($response);
                    }
                } else
                {
                    LogManager::store('[POST] Tentative de communication avec un bitsky non autorisé (ID utilisateur: ' . $uniq_id . ')', 2);
                    return $this->forbidden('intermediaryNotReachable');
                }
            } else
            {
                LogManager::store('[POST] Impossible de récupérer les posts d\'un utilisateur (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('intermediaryNotReachable');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération des posts d\'un utilisateur de liaison sans fournir les paramètres (ID utilisateur: ' . $_POST['uniq_id'] . ')', 2);
            return $this->forbidden('noInfos');
        }
    }

    public function getLocalScore()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

    public function getScore()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocalScore();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/get_localpost_score';

                    $favorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    return $favorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération du score d\'un post sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération du score d\'un post avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function addLocalFavorite()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $post_id = htmlspecialchars($_POST['post_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                $post = PostModel::find($post_id);
                $post->favorites = $post->favorites + 1;
                $post->save();

                $postFavorite = PostFavoriteModel::create([
                    'post_id' => $post_id,
                    'user_uniq_id' => $uniq_id
                ]);

                if($postFavorite)
                {
                    if($uniq_id != $post->owner_uniq_id)
                    {
                        if(!$authorizedForeign)
                        {
                            Notification::create([
                                'receiver_uniq_id' => $post->owner_uniq_id,
                                'sender_uniq_id' => $uniq_id,
                                'element_id' => $post->id,
                                'element_type' => 'post',
                                'action' => 'addFavorite'
                            ]);
                        } else
                        {
                            $bitsky_ip = htmlspecialchars($_POST['bitsky_ip']);
                            $linkController = new Link();
                            $key = json_decode($linkController->getKeyOfIp($bitsky_ip), true);
                            $key = $key['data'];
                            $link = \Model\Link::where('bitsky_key', $key)->first();

                            Notification::create([
                                'receiver_uniq_id' => $post->owner_uniq_id,
                                'sender_uniq_id' => $uniq_id,
                                'element_id' => $post->id,
                                'element_type' => 'post',
                                'action' => 'addFavorite',
                                'link_id' => $link->id
                            ]);
                        }
                    }
                } else
                {
                    return $this->forbidden('cantCreatePostFavorite');
                }

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

    public function addFavorite()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->addLocalFavorite();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_add_local_favorite';
                    $external_ip = exec('curl http://ipecho.net/plain; echo');

                    $favorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id'],
                            'bitsky_ip' => $external_ip
                        ]
                    );

                    return $favorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative d\'ajout d\'un post en favoris sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative d\'ajout d\'un post en favoris avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function removeLocalFavorite()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $post_id = htmlspecialchars($_POST['post_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

    public function removeFavorite()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->removeLocalFavorite();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_remove_local_favorite';

                    $favorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    return $favorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative de suppression d\'un post en favoris sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de suppression d\'un post en favoris avec un token invalide (ID utilisateur: ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalFavoriteOfUser()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $post_id = htmlspecialchars($_POST['post_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

    public function getFavoriteOfUser()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocalFavoriteOfUser();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_get_local_user_favorite';

                    $favorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    return $favorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération d\'un favoris de post sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération d\'un favoris de post avec un token invalide (ID utilisateur: ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalTrends()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if((!empty($_POST['token']) && !empty($_POST['uniq_id'])) || $authorizedForeign)
        {
            $token = !empty($_POST['token']) ? htmlspecialchars($_POST['token']) : false;
            $uniq_id = !empty($_POST['uniq_id']) ? htmlspecialchars($_POST['uniq_id']) : 'linkedDevice';

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

                    if(!empty($user)) {
                        array_push($trends, [
                            'name' => $tag->name,
                            'score' => $tag->score,
                            'post' => [
                                'id' => $post->id,
                                'content' => $post->content,
                                'owner'   => ['id' => $user->id, 'name' => $user->firstname . ' ' . $user->lastname]
                            ]
                        ]);
                    }
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

    public function getTrends()
    {
        if(!empty($_POST['uniq_id']) && !empty($_POST['token']))
        {
            $localTrends = $this->getLocalTrends();
            $localTrends = json_decode($localTrends, true);

            $linkedDevices = \Model\Link::all();

            if(count($linkedDevices) == 0 || empty($linkedDevices)) {
                return json_encode(['success' => true, 'trends' => $localTrends['trends']]);
            }

            $strangerTrends = [];

            $uniq_id = htmlspecialchars($_POST['uniq_id']);

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
                    $response = $this->callAPI(
                        'POST',
                        'http://' . $link['foreign_ip'] . '/get_localtrends'
                    );

                    $response = json_decode($response, true);

                    if($response['success'])
                    {
                        foreach($response['trends'] as $trend)
                        {
                            $trend['fromStranger'] = $link['foreign_ip'];
                            array_push($strangerTrends, $trend);
                        }
                    }
                }

                $trends = array_merge($localTrends['trends'], $strangerTrends);
                $mixedTrends = [];

                foreach($trends as $keyTrend => $trend)
                {
                    if(empty($mixedTrends))
                    {
                        array_push($mixedTrends, $trend);
                        unset($trends[$keyTrend]);
                        continue;
                    } else
                    {
                        $push = true;

                        foreach($mixedTrends as $k => $mixedTrend)
                        {
                            if($trend['name'] == $mixedTrend['name'])
                            {
                                $mixedTrends[$k]['score'] = $mixedTrend['score'] + $trend['score'];
                                $push = false;
                                break;
                            }
                        }

                        if($push)
                        {
                            array_push($mixedTrends, $trend);
                            unset($trends[$keyTrend]);
                            continue;
                        }
                    }
                }

                usort($mixedTrends,function($first,$second){
                    return $first['score'] < $second['score'];
                });

                $mixedTrends = array_slice($mixedTrends, 0, 3);

                return json_encode(['success' => true, 'trends' => $mixedTrends]);
            } else
            {
                LogManager::store('[POST] Impossible de récupérer les sujets du moment (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('intermediaryNotReachable');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération des sujets du moment de liaison sans fournir les paramètres (ID utilisateur: ' . $_POST['uniq_id'] . ')', 2);
            return $this->forbidden('noInfos');
        }
    }

    public function getAllComments()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            $comments = PostCommentModel::all();

            foreach($comments as $comment)
            {
                if(!empty($comment->link_id))
                {
                    $linkController = new Link();
                    $_POST['link_id'] = $comment->link_id;
                    $link = json_decode($linkController->getLinkById(), true);
                    $link = $link['link'];

                    $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                    $bitsky_ip = $bitsky_ip['data'];

                    $owner = json_decode($this->callAPI(
                        'POST',
                        'http://localhost/get_user_by_uniq_id',
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'user_uniq_id' => $comment->owner_id,
                            'bitsky_ip' => $bitsky_ip
                        ]
                    ), true);

                    $comment->owner = $owner['user'];
                } else
                {
                    $comment->owner = UserModel::where('uniq_id', $comment->owner_id)->first(['id', 'firstname', 'lastname', 'rank', 'avatar']);
                }
            }

            return json_encode(['success' => true, 'comments' => $comments]);
        } else
        {
            LogManager::store('[POST] Tentative de récupération des commentaires avec un token invalide (ID utilisateur: ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalComments()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if((!empty($_POST['token']) && !empty($_POST['uniq_id'])) || $authorizedForeign)
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                if(!empty($_POST['post_id']))
                {
                    $post_id = htmlspecialchars($_POST['post_id']);
                    $comments = PostCommentModel::where('post_id', $post_id)->get();

                    foreach($comments as $comment)
                    {
                        if(!empty($comment->link_id))
                        {
                            $linkController = new Link();
                            $_POST['link_id'] = $comment->link_id;
                            $link = json_decode($linkController->getLinkById(), true);
                            $link = $link['link'];

                            $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                            $bitsky_ip = $bitsky_ip['data'];

                            $owner = json_decode($this->callAPI(
                                'POST',
                                'http://localhost/get_user_by_uniq_id',
                                [
                                    'uniq_id' => $uniq_id,
                                    'token' => $token,
                                    'user_uniq_id' => $comment->owner_id,
                                    'bitsky_ip' => $bitsky_ip
                                ]
                            ), true);

                            $comment->owner = $owner['user'];
                        } else
                        {
                            $comment->owner = UserModel::where('uniq_id', $comment->owner_id)->first(['id', 'firstname', 'lastname', 'rank', 'avatar']);
                        }
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

    public function getComments()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocalComments();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_get_local_comments';

                    $comments = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    $linkComments = json_decode($comments, true);

                    if($linkComments['success'])
                    {
                        $comments = [];

                        foreach($linkComments['comments'] as $comment)
                        {
                            if(empty($comment['link_id']))
                            {
                                $comment['fromStranger'] = $_POST['bitsky_ip'];
                            }

                            array_push($comments, $comment);
                        }

                        return json_encode(['success' => true, 'comments' => $comments]);
                    } else
                    {
                        return 'cantGetForeignPosts';
                    }
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération des commentaires d\'un post sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération des commentaires d\'un post avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function addLocalCommentFavorite()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_comment_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $post_comment_id = htmlspecialchars($_POST['post_comment_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

    public function addCommentFavorite()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_comment_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->addLocalCommentFavorite();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_add_local_comment_favorite';

                    $commentFavorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_comment_id' => $_POST['post_comment_id']
                        ]
                    );

                    return $commentFavorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative d\'ajout de favoris de commentaire sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative d\'ajout de favoris de commentaire avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function removeLocalCommentFavorite()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_comment_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $post_comment_id = htmlspecialchars($_POST['post_comment_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                $comment = PostCommentModel::find($post_comment_id);
                $comment->favorites = $comment->favorites - 1;
                $comment->save();

                PostCommentFavoriteModel::where('post_comment_id', $post_comment_id)->where('user_uniq_id', $uniq_id)->delete();

                return json_encode(['success' => true]);
            }else
            {
                LogManager::store('[POST] Tentative de suppression d\'un commentaire en favoris avec un token invalide (ID utilisateur: '.$uniq_id.')', 2);
                return $this->forbidden('invalidToken');
            }
        }else
        {
            return $this->forbidden('noInfos');
        }
    }

    public function removeCommentFavorite()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_comment_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->removeLocalCommentFavorite();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_remove_local_comment_favorite';

                    $commentFavorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_comment_id' => $_POST['post_comment_id']
                        ]
                    );

                    return $commentFavorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative de suppression de favoris de commentaire sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de suppression de favoris de commentaire avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalCommentFavoriteOfUser()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['post_comment_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $post_comment_id = htmlspecialchars($_POST['post_comment_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

    public function getCommentFavoriteOfUser()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_comment_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocalCommentFavoriteOfUser();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_get_local_user_comment_favorite';

                    $commentFavorite = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_comment_id' => $_POST['post_comment_id']
                        ]
                    );

                    return $commentFavorite;
                }
            } else
            {
                LogManager::store('[POST] Tentative d\'ajout de favoris de commentaire sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative d\'ajout de favoris de commentaire avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalCommentsCount()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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

    public function getCommentsCount()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocalCommentsCount();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/get_localcommentscount';

                    $commentsCount = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    return $commentsCount;
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération du nombre de commentaires d\'un post sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération du nombre de commentaires d\'un post avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function getLocalBestComments()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if((!empty($_POST['token']) && !empty($_POST['uniq_id'])))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $remoteAddress = new RemoteAddress();

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                if(!empty($_POST['post_id']))
                {
                    $external_ip = exec('curl http://ipecho.net/plain; echo');
                    $post_id = htmlspecialchars($_POST['post_id']);
                    $comments = PostCommentModel::where('post_id', $post_id)->orderBy('favorites', 'desc')->take(3)->get();

                    foreach($comments as $comment)
                    {
                        if(!empty($comment->link_id))
                        {
                            $linkController = new Link();
                            $_POST['link_id'] = $comment->link_id;
                            $link = json_decode($linkController->getLinkById(), true);
                            $link = $link['link'];

                            $bitsky_ip = json_decode($linkController->getIpOfKey($link['bitsky_key']), true);
                            $bitsky_ip = $bitsky_ip['data'];

                            $owner = json_decode($this->callAPI(
                                'POST',
                                'http://localhost/get_user_by_uniq_id',
                                [
                                    'uniq_id' => $uniq_id,
                                    'token' => $token,
                                    'user_uniq_id' => $comment->owner_id,
                                    'bitsky_ip' => $bitsky_ip
                                ]
                            ), true);

                            $comment->owner = $owner['user'];

                            if($bitsky_ip != $remoteAddress->getIpAddress())
                            {
                                $comment->fromStranger  = $external_ip;
                            }
                        } else
                        {
                            if($authorizedForeign)
                            {
                                $comment->fromStranger  = $external_ip;
                            }

                            $comment->owner = UserModel::where('uniq_id', $comment->owner_id)->first(['id', 'firstname', 'lastname', 'avatar']);
                        }

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

    public function getBestComments()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->getLocalBestComments();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/get_localbestcomments';

                    $bestComments = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id']
                        ]
                    );

                    return $bestComments;
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération des meilleurs commentaires sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération des meilleurs commentaires avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function addLocalComment()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
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
                            $comment = null;

                            if($authorizedForeign && !empty($_POST['bitsky_ip']))
                            {
                                $bitsky_ip = htmlspecialchars($_POST['bitsky_ip']);
                                $linkController = new Link();
                                $key = json_decode($linkController->getKeyOfIp($bitsky_ip), true);
                                $key = $key['data'];
                                $link = \Model\Link::where('bitsky_key', $key)->first();

                                $comment = PostCommentModel::create([
                                    'owner_id' => $uniq_id,
                                    'post_id' => $post_id,
                                    'content' => $content,
                                    'link_id' => $link->id
                                ]);

                                if($uniq_id != $post->owner_uniq_id)
                                {
                                    Notification::create([
                                        'receiver_uniq_id' => $post->owner_uniq_id,
                                        'sender_uniq_id' => $uniq_id,
                                        'element_id' => $post->id,
                                        'element_type' => 'post',
                                        'action' => 'addComment',
                                        'link_id' => $link->id
                                    ]);
                                }
                            } else
                            {
                                $comment = PostCommentModel::create([
                                    'owner_id' => $uniq_id,
                                    'post_id' => $post_id,
                                    'content' => $content
                                ]);

                                if($uniq_id != $post->owner_uniq_id)
                                {
                                    Notification::create([
                                        'receiver_uniq_id' => $post->owner_uniq_id,
                                        'sender_uniq_id' => $uniq_id,
                                        'element_id' => $post->id,
                                        'element_type' => 'post',
                                        'action' => 'addComment'
                                    ]);
                                }
                            }

                            if($comment != null)
                            {
                                $post->comments = $post->comments + 1;
                                $post->save();

                                $owner = json_decode($this->callAPI(
                                    'POST',
                                    'http://localhost/get_user_by_uniq_id',
                                    [
                                        'uniq_id' => $uniq_id,
                                        'token' => $token,
                                        'user_uniq_id' => $uniq_id,
                                        'bitsky_ip' => !empty($bitsky_ip) ? $bitsky_ip : null
                                    ]
                                ), true);

                                $comment->owner = $owner['user'];

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

    public function addComment()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['post_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->addLocalComment();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_add_local_comment';
                    $external_ip = exec('curl http://ipecho.net/plain; echo');

                    $comment = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'post_id' => $_POST['post_id'],
                            'content' => $_POST['content'],
                            'bitsky_ip' => $external_ip
                        ]
                    );

                    return $comment;
                }
            } else
            {
                LogManager::store('[POST] Tentative d\'ajout de commentaire sans fournir un id de post (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative d\'ajout de commentaire avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function removeLocalComment()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if(!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['comment_id']))
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);
            $comment_id = htmlspecialchars($_POST['comment_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if($verify->success || $authorizedForeign)
            {
                $comment = PostCommentModel::where('id', $comment_id)->first();
                $statement = false;

                $user = UserModel::where('uniq_id', $uniq_id)->first();

                if(!empty($user))
                {
                    $statement = $user->rank == 2 || $user->uniq_id == $comment->owner_id;
                }

                if($authorizedForeign && $_POST['uniq_id'] == $comment->owner_id)
                {
                    $statement = true;
                }

                if($statement)
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

    public function removeComment()
    {
        $check = $this->checkUserToken();

        if(!empty($check))
        {
            if (!empty($_POST['comment_id']))
            {
                if (empty($_POST['bitsky_ip']))
                {
                    return $this->removeLocalComment();
                } else
                {
                    $url = htmlspecialchars($_POST['bitsky_ip']) . '/post_remove_local_comment';

                    $response = $this->callAPI(
                        'POST',
                        $url,
                        [
                            'uniq_id' => $check['uniq_id'],
                            'token' => $check['token'],
                            'comment_id' => $_POST['comment_id']
                        ]
                    );

                    return $response;
                }
            } else
            {
                LogManager::store('[POST] Tentative de suppression d\'un commentaire sans fournir son ID (ID utilisateur: ' . $check['uniq_id'] . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else
        {
            LogManager::store('[POST] Tentative de suppression d\'un commentaire avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function update()
    {
        $check = $this->checkUserToken();

        if($check)
        {
            $currentUser = UserModel::where('uniq_id', $check['uniq_id'])->first(['rank']);

            if($currentUser && $currentUser['rank'] == 2)
            {
                if(!empty($_POST['post_id']))
                {
                    $post_id = htmlspecialchars($_POST['post_id']);

                    $currentPost = PostModel::where('id', $post_id)->first();

                    if($currentPost)
                    {
                        if(!empty($_POST['content']))
                        {
                            $post_content = htmlspecialchars($_POST['content']);

                            $currentPost->content = $post_content;
                            $currentPost->save();

                            return json_encode(['success' => true]);
                        }else
                        {
                            return $this->forbidden('contentRequired');
                        }
                    }else
                    {
                        return $this->forbidden('postNotFound');
                    }
                }else
                {
                    return $this->forbidden('postIdRequired');
                }
            }else
            {
                LogManager::store('[POST] Tentative de modification d\'une publication avec un rang trop bas (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('needAdminRights');
            }

        }else
        {
            LogManager::store('[POST] Tentative de modification d\'une publication avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }

    public function updateComment()
    {
        $check = $this->checkUserToken();

        if($check)
        {
            $currentUser = UserModel::where('uniq_id', $check['uniq_id'])->first(['rank']);

            if($currentUser && $currentUser['rank'] == 2)
            {
                if(!empty($_POST['comment_id']))
                {
                    $comment_id = htmlspecialchars($_POST['comment_id']);

                    $currentComment = PostCommentModel::where('id', $comment_id)->first();

                    if($currentComment)
                    {
                        if(!empty($_POST['content']))
                        {
                            $comment_content = htmlspecialchars($_POST['content']);

                            $currentComment->content = $comment_content;
                            $currentComment->save();

                            return json_encode(['success' => true]);
                        }else
                        {
                            return $this->forbidden('contentRequired');
                        }
                    }else
                    {
                        return $this->forbidden('commentNotFound');
                    }
                }else
                {
                    return $this->forbidden('commentIdRequired');
                }
            }else
            {
                LogManager::store('[POST] Tentative de modification d\'un commentaire avec un rang trop bas (ID utilisateur: '.$check['uniq_id'].')', 2);
                return $this->forbidden('needAdminRights');
            }

        }else
        {
            LogManager::store('[POST] Tentative de modification d\'un commentaire avec un token invalide (ID utilisateur:  ?)', 2);
            return $this->forbidden('invalidToken');
        }
    }
}
