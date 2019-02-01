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

    public function create()
    {
        $notEmpty = !empty($_POST['token']) && !empty($_POST['uniq_id']) &&
                                !empty($_POST['lastname']) && !empty($_POST['firstname']) &&
                                !empty($_POST['email']) && !empty($_POST['rank']) &&
                                !empty($_POST['password']) && !empty($_POST['repeatPassword']) &&
                                !empty($_POST['biography']) && !empty($_POST['sex']) &&
                                !empty($_POST['job']) && !empty($_POST['birthdate']) &&
                                !empty($_POST['birthplace']) && !empty($_POST['relationshipstatus']) &&
                                !empty($_POST['livingplace']);

        if($notEmpty) {
            if (!empty($_POST['token']) && !empty($_POST['uniq_id'])) {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);
                $user = UserModel::where('uniq_id', $uniq_id)->first();
                $verify = json_decode($this->authService->verify($token, $uniq_id));

                if ($verify->success) {
                    $user = UserModel::where('uniq_id', $uniq_id)->first();

                    if($user['rank'] == 2) {
                        $received = [
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
                        $isEmailOk = filter_var($received['email'], FILTER_VALIDATE_EMAIL);
                        $isRankOk = in_array($received['rank'], $ranks);
                        $isPasswordOk = strlen($received['password']) >= 8;
                        $isRepeatPasswordOk = strlen($received['repeatPassword']) >= 8;
                        $arePasswordOk = $received['password'] == $received['repeatPassword'];
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
                            if(count(UserModel::where('email', $received['email'])->get()) == 0)
                            {
                                $newUser = new UserModel();
                                $newUser->uniq_id = $received['uniq_id'];
                                $newUser->email = $received['email'];
                                $newUser->password = password_hash($received['password'], PASSWORD_BCRYPT);
                                $newUser->lastname = $received['lastname'];
                                $newUser->firstname = $received['firstname'];
                                $newUser->rank = $received['rank'];
                                $newUser->biography = $received['biography'];
                                $newUser->sex = $received['sex'];
                                $newUser->job = $received['job'];
                                $newUser->birthdate = $received['birthdate'];
                                $newUser->birthplace = $received['birthplace'];
                                $newUser->relationshipstatus = $received['relationshipstatus'];
                                $newUser->livingplace = $received['livingplace'];
                                $newUser->avatar = $_POST['avatar'];
                                $newUser->firsttime = 0;
                                $newUser->save();

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
            return $this->forbidden('Veuillez remplir tous les champs !');
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
                if($me['rank'] == 2)
                {
                    $id = htmlspecialchars($_POST['user_id']);
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
