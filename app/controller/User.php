<?php

namespace Controller;

use \Controller\Auth;
use \Kernel\LogManager;
use \Model\User as UserModel;
use \Model\Post as PostModel;
use \Model\PostFavorite as PostFavoriteModel;
use \Model\PostComment as PostCommentModel;
use \Model\PostCommentFavorite as PostCommentFavoriteModel;
use \Model\Tag as TagModel;

class User extends Controller
{
    public function __construct()
    {
        $this->authService = new Auth();
    }

    public function getAll()
    {
        if (!empty($_POST['token']) && !empty($_POST['uniq_id'])) {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if ($verify->success) {
                $user = UserModel::where('uniq_id', $uniq_id)->first();

                if ($user['rank'] == 2) {
                    $users = UserModel::orderBy('rank', 'desc')->get();
                    return json_encode(['success' => true, 'users' => $users]);
                } else 
                {
                    LogManager::store('[POST] Tentative de récupération des utilisateurs avec un rang trop bas (ID utilisateur: ' . $uniq_id . ')', 2);
                    return $this->forbidden('forbidden');
                }
            } else 
            {
                LogManager::store('[POST] Tentative de récupération des utilisateurs avec un token invalide (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else {
            return $this->forbidden('noInfos');
        }
    }

    public function getById()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if ((!empty($_POST['token']) && !empty($_POST['uniq_id'])) || $authorizedForeign) {
            $token = !empty($_POST['token']) ? htmlspecialchars($_POST['token']) : false;
            $uniq_id = !empty($_POST['uniq_id']) ? htmlspecialchars($_POST['uniq_id']) : 'linkedDevice';

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if ($verify->success || $authorizedForeign) {

                if(!empty($_POST['user_id'])) {
                    $userId = htmlspecialchars($_POST['user_id']);

                    $user = UserModel::where('id', $userId)->first();

                    if($user != null) {

                        unset($user['password']);
                        return json_encode(['success' => true, 'user' => $user]);

                    }else {

                        return $this->forbidden('notFound');
                    }
                } else {
                    LogManager::store('[POST] Tentative de récupération d\'un utilisateur sans fournir son ID (ID utilisateur: ' . $uniq_id . ')', 2);
                    return $this->forbidden('noID');
                }
            } else 
            {
                LogManager::store('[POST] Tentative de récupération de l\'utilisateur avec un token invalide (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else {
            return $this->forbidden('noInfos');
        }
    }

    public function strangerGetById()
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
                        'http://' . $link['foreign_ip'] . '/get_user',
                        [
                            'user_id' => $user_id
                        ]
                    );

                    $response = json_decode($response, true);

                    if($response['success'])
                    {
                        return json_encode(['success' => true, 'user' => $response['user']]);
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
                LogManager::store('[POST] Impossible de récupérer les liaisons (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('intermediaryNotReachable');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération de l\'utilisateur de liaison sans fournir les paramètres (ID utilisateur: ' . $_POST['uniq_id'] . ')', 2);
            return $this->forbidden('noInfos');
        }
    }

    public function getFavoritesTrends()
    {
        $authorizedForeign = $this->isAuthorizedForeign();

        if ((!empty($_POST['token']) && !empty($_POST['uniq_id'])) || $authorizedForeign) {
            $token = !empty($_POST['token']) ? htmlspecialchars($_POST['token']) : false;
            $uniq_id = !empty($_POST['uniq_id']) ? htmlspecialchars($_POST['uniq_id']) : 'linkedDevice';

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if ($verify->success || $authorizedForeign) {

                if(!empty($_POST['user_id'])) {
                    $userId = htmlspecialchars($_POST['user_id']);

                    $user = UserModel::where('id', $userId)->first();

                    if($user != null) {
                        $posts = PostModel::where('owner_uniq_id', $user->uniq_id)->get();
                        $trendsCount = [];

                        foreach($posts as $post)
                        {
                            $trendID = $post->tag_id;
                            $alreadyInArray = false;

                            foreach ($trendsCount as $key => $trendCount) {
                                if($trendCount['id'] == $trendID) {
                                    $alreadyInArray = true;
                                    $trendCount['count'] = $trendCount['count'] + 1;
                                }

                                $trendsCount[$key] = $trendCount;
                            }

                            if(!$alreadyInArray) {
                                array_push($trendsCount, ['id' => $trendID, 'count' => 1]);
                            }
                        }

                        foreach($trendsCount as $key => $trendCount)
                        {
                            $tag = TagModel::where('id', $trendCount['id'])->first();
                            $trendsCount[$key]['name'] = $tag->name;
                        }

                        return json_encode(['success' => true, 'favoritesTrends' => $trendsCount]);
                    }else {

                        return $this->forbidden('notFound');
                    }
                }
            } else
            {
                LogManager::store('[POST] Tentative de récupération de l\'utilisateur avec un token invalide (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('invalidToken');
            }
        } else {
            return $this->forbidden('noInfos');
        }
    }

    public function strangerGetFavoritesTrends()
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
                        'http://' . $link['foreign_ip'] . '/get_favoritestrends',
                        [
                            'user_id' => $user_id
                        ]
                    );

                    $response = json_decode($response, true);

                    if($response['success'])
                    {
                        return json_encode(['success' => true, 'favoritesTrends' => $response['favoritesTrends']]);
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
                LogManager::store('[POST] Impossible de récupérer les liaisons (ID utilisateur: ' . $uniq_id . ')', 2);
                return $this->forbidden('intermediaryNotReachable');
            }
        } else
        {
            LogManager::store('[POST] Tentative de récupération de l\'utilisateur de liaison sans fournir les paramètres (ID utilisateur: ' . $_POST['uniq_id'] . ')', 2);
            return $this->forbidden('noInfos');
        }
    }

    public function createOrUpdate()
    {
        $type = null; 

        if(!empty($_POST['type'])) $type = htmlspecialchars($_POST['type']);

        $notEmpty = !empty($_POST['token']) && !empty($_POST['uniq_id']) &&
                    !empty($_POST['lastname']) && !empty($_POST['firstname']) &&
                    !empty($_POST['email']) && !empty($_POST['rank']) &&
                    (!empty($_POST['password']) || $type == 'UPDATE') &&
                    (!empty($_POST['repeatPassword']) || $type == 'UPDATE') &&
                    !empty($_POST['biography']) && !empty($_POST['sex']) &&
                    !empty($_POST['job']) && !empty($_POST['birthdate']) &&
                    !empty($_POST['birthplace']) && !empty($_POST['relationshipstatus']) &&
                    !empty($_POST['livingplace']);

        if($notEmpty) {
            if (!empty($_POST['token']) && !empty($_POST['uniq_id'])) {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if ($verify->success) {
                    $user = UserModel::where('uniq_id', $uniq_id)->first();

                    if($user['rank'] == 2 || ($type == 'UPDATE' && $user->id == htmlspecialchars($_POST['user_id']))) {
                        $received = [
                            "user_id" => (!empty($_POST['user_id']) ? htmlspecialchars($_POST['user_id']) : null),
                            "uniq_id" => md5(uniqid()),
                            "lastname" => htmlspecialchars(trim($_POST['lastname'])),
                            "firstname" => htmlspecialchars(trim($_POST['firstname'])),
                            "email" => htmlspecialchars(trim($_POST['email'])),
                            "rank" => htmlspecialchars(trim($_POST['rank'])),
                            "password" => htmlspecialchars($_POST['password']),
                            "repeatPassword" => htmlspecialchars($_POST['repeatPassword']),
                            "biography" => htmlspecialchars(trim($_POST['biography'])),
                            "sex" => htmlspecialchars(trim($_POST['sex'])),
                            "job" => htmlspecialchars(trim($_POST['job'])),
                            "birthdate" => htmlspecialchars(trim($_POST['birthdate'])),
                            "birthplace" => htmlspecialchars(trim($_POST['birthplace'])),
                            "relationshipstatus" => htmlspecialchars(trim($_POST['relationshipstatus'])),
                            "livingplace" => htmlspecialchars(trim($_POST['livingplace'])),
                        ];

                        $ranks = [1, 2];
    
                        $isLastnameOk = strlen($received['lastname']) >= 2;
                        $isFirstnameOk = strlen($received['firstname']) >= 2;
                        $isEmailOk = preg_match('/^[a-zA-Z]\w+(?:\.[a-zA-Z]\w+){0,3}@[a-zA-Z]\w+(?:\.[a-zA-Z]\w+){1,3}$/', $received['email']);
                        $isRankOk = in_array($received['rank'], $ranks);
                        $isPasswordOk = strlen($received['password']) >= 8 || $type == 'UPDATE';
                        $isRepeatPasswordOk = strlen($received['repeatPassword']) >= 8 || $type == 'UPDATE';
                        $arePasswordOk = $received['password'] == $received['repeatPassword'] || $type == 'UPDATE';
                        $isBiographyOk = strlen($received['biography']) >= 10;
                        $isSexOk = in_array($received['sex'], ['Homme', 'Femme', 'Autre']);
                        $isJobOk = strlen($received['job']) >= 3;
                        $isBirthdateOk = strlen($received['birthdate']) == 10;
                        $isBirthplaceOk = strlen($received['birthplace']) >= 3;
                        $isRelationshipstatusOk = in_array($received['relationshipstatus'], ['Célibataire', 'En couple', 'Marié(e)', 'Veuf(ve)', 'Non précisé']);
                        $isLivingplaceOk = strlen($received['livingplace']) >= 3;
                        $isFormOk = $isLastnameOk && $isFirstnameOk && $isEmailOk && $isRankOk && $isPasswordOk && $isRepeatPasswordOk && $arePasswordOk && $isBiographyOk && $isSexOk && $isJobOk && $isBirthdateOk && $isBirthplaceOk && $isRelationshipstatusOk && $isLivingplaceOk;
                        if ($isFormOk)
                        {
                            if((count(UserModel::where('email', $received['email'])->get()) == 0 || $type == 'UPDATE'))
                            {
                                $user = null;
                                
                                if($type == 'ADD') $user = new UserModel();
                                else $user = UserModel::where('id', $received['user_id'])->first();
                                
                                if($type == 'ADD') $user->uniq_id = $received['uniq_id'];

                                $user->email = $received['email'];

                                if($type == 'ADD' || ($type == 'UPDATE' &&  !empty($received['password']) && strlen($received['password']) >= 8))
                                {
                                    $user->password = password_hash($received['password'], PASSWORD_BCRYPT);
                                }
                                $user->lastname = $received['lastname'];
                                $user->firstname = $received['firstname'];
                                $user->rank = $received['rank'];
                                $user->biography = $received['biography'];
                                $user->sex = $received['sex'];
                                $user->job = $received['job'];
                                $user->birthdate = $received['birthdate'];
                                $user->birthplace = $received['birthplace'];
                                $user->relationshipstatus = $received['relationshipstatus'];
                                $user->livingplace = $received['livingplace'];
                                $user->avatar = $_POST['avatar'];
                                $user->firsttime = 0;
                                $user->save();

                                return json_encode(['success' => true]);
                            } else 
                            {
                                return $this->forbidden('Cette adresse email est déjà utilisée !');
                            }
                        } else 
                        {
                            return $this->forbidden('Impossible de procéder à l\'ajout de l\'utilisateur !');
                        }                  
                    } else 
                    {
                        LogManager::store('[POST] Tentative de création d\'un utilisateur avec un rang trop bas (ID utilisateur: ' . $uniq_id . ')', 2);
                        return $this->forbidden('Vous n\'avez pas le rang requis pour cette action !');
                    }
                } else 
                {
                    LogManager::store('[POST] Tentative de création d\'un utilisateur avec un token invalide (ID utilisateur: ' . $uniq_id . ')', 2);
                    return $this->forbidden('Impossible de déterminer votre identité !');
                }
            } else 
            {
                return $this->forbidden('Impossible de déterminer votre identité !');      
            }
        }else 
        {
            return $this->forbidden('Veuillez remplir tous les champs ! ');
        }
    }

    public function delete()
    {
        if (!empty($_POST['token']) && !empty($_POST['uniq_id']) && !empty($_POST['user_id'])) 
        {
            $token = htmlspecialchars($_POST['token']);
            $uniq_id = htmlspecialchars($_POST['uniq_id']);

            $verify = json_decode($this->authService->verify($token, $uniq_id));

            if ($verify->success)
            {
                $me = UserModel::where('uniq_id', $uniq_id)->first();
                $id = htmlspecialchars($_POST['user_id']);

                if($me['rank'] == 2 || $me['id'] == $id)
                {
                    $user = UserModel::where('id', $id)->first();
                    $posts = PostModel::where('owner_uniq_id', $user['uniq_id']);
                    $postFavorites = PostFavoriteModel::where('user_uniq_id', $user['uniq_id']);
                    $postComments = PostCommentModel::where('owner_id', $user['uniq_id']);
                    $postCommentFavorites = PostCommentFavoriteModel::where('user_uniq_id', $user['uniq_id']);
                    if($user['rank'] == 2)
                    {
                        if($user['uniq_id'] == $me['uniq_id'])
                        {
                            $this->deleteAllUserData($user, $posts, $postFavorites, $postComments, $postCommentFavorites);
                            return json_encode(['success' => true]);
                        } else
                        {
                            return $this->forbidden('cantDeleteAnAdmin');
                        }
                    } else 
                    {
                        $this->deleteAllUserData($user, $posts, $postFavorites, $postComments, $postCommentFavorites);
                        return json_encode(['success' => true]);
                    }
                }else 
                {
                    return $this->forbidden('invalidRank');
                }          
            }else 
            {
                return $this->forbidden('invalidToken');
            }      
        }else 
        {
            return $this->forbidden('noInfos');
        }
    }

    public function deleteAllUserData($user, $posts, $postFavorites, $postComments, $postCommentFavorites) 
    {
        // Deleting user
        $user->delete();
        
        // Deleting tags used by user's posts
        foreach($posts->get() as $post) 
        {
            $tag = TagModel::where('id', $post->tag_id)->first();
            if($tag->uses > 1)
            {
                $tag->uses = $tag->uses - 1;
                $tag->save();
            } else 
            {
                $tag->delete();
            }
        }

        // Deleting user's favorites and decrementing concerned posts
        foreach($postFavorites->get() as $postFavorite) 
        {
            $post = PostModel::where('id', $postFavorite->post_id)->first();
            $post->favorites = $post->favorites - 1;
            $post->save();
        }

        $postFavorites->delete();

        // Deleting user's post comments and the comments favorites and decrementing concerned posts
        foreach($postComments->get() as $postComment) 
        {
            $favorites = PostCommentFavoriteModel::where('post_comment_id', $postComment->id)->delete();
            $post = PostModel::where('id', $postComment->post_id)->first();
            $post->comments = $post->comments - 1;
            $post->save();
        }

        $postComments->delete();

        // Deleting user's post comment favorites
        foreach($postCommentFavorites->get() as $postCommentFavorite) 
        {
            $comment = PostCommentModel::where('id', $postCommentFavorite->post_comment_id)->first();
            $comment->favorites = $comment->favorites - 1;
            $comment->save();
        }

        $postCommentFavorites->delete();

        // Deleting user's posts and the posts favorites
        foreach($posts->get() as $post)
        {
            PostFavoriteModel::where('post_id', $post->id)->delete();
        }

        $posts->delete();
    }
}
