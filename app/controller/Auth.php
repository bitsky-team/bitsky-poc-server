<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Model\User;
    use \Kernel\LogManager;
    use \Model\User as UserModel;

    class Auth extends Controller
    {
        public function login()
        {
            $notEmpty = !empty($_POST['email']) && !empty($_POST['password']);
            
            if($notEmpty)
            {
                $received = [
                    "email" => htmlspecialchars($_POST['email']),
                    "password" => htmlspecialchars($_POST['password'])
                ];

                $emailCheck = preg_match('/^[a-zA-Z]\w+(?:\.[a-zA-Z]\w+){0,3}@[a-zA-Z]\w+(?:\.[a-zA-Z]\w+){1,3}$/', $received['email']);
                $passwordCheckLength = strlen($received['password']) >= 8;
                
                if($emailCheck && $passwordCheckLength)
                {
                    if(count(User::where('email', $received['email'])->get()) != 0)
                    {
                        $user = User::where('email', $received['email'])->first();
                        
                        if(password_verify($received['password'], $user['password']))
                        {
                            $token['id'] = $user['id'];
                            $token['lastname'] = $user['lastname'];
                            $token['firstname'] = $user['firstname'];
                            $token['rank'] = $user['rank'];
                            $token['created_at'] = time();
                            $token['lifetime'] = 86400;
                            $token = JWT::encode($token);
                            $user->update(['token' => password_hash($token, PASSWORD_BCRYPT)]);

                            return json_encode(['success' => true, 'message' => $token, 'uniq_id' => $user['uniq_id'], 'avatar' => $user['avatar']]);
                        }else
                        {
                            return $this->forbidden('Mot de passe incorrect !');
                        }
                    }else
                    {
                        return $this->forbidden('Ce compte n\'existe pas !');
                    }
                }
            }else
            {
                return $this->forbidden('Veuillez remplir tous les champs !');
            }
        }

        public function register()
        {
            $notEmpty = !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['repeatPassword']) && !empty($_POST['lastname']) && !empty($_POST['firstname']);

            if($notEmpty)
            {
                $received = [
                    "uniq_id" =>  md5(uniqid()),
                    "email" =>  htmlspecialchars($_POST['email']),
                    "password" =>  htmlspecialchars($_POST['password']),
                    "repeatPassword" =>  htmlspecialchars($_POST['repeatPassword']),
                    "lastname" =>  htmlspecialchars(ucfirst($_POST['lastname'])),
                    "firstname" =>  htmlspecialchars(ucfirst($_POST['firstname']))
                ];

                $emailCheck = preg_match('/^[a-zA-Z]\w+(?:\.[a-zA-Z]\w+){0,3}@[a-zA-Z]\w+(?:\.[a-zA-Z]\w+){1,3}$/', $received['email']);
                $passwordCheckLength = strlen($received['password']) >= 8;
                $repeatPasswordCheckLength = strlen($received['repeatPassword']) >= 8;
                $passwordCheckEquality = $received['password'] == $received['repeatPassword'];
                $lastnameCheck = strlen($received['lastname']) >= 2;
                $firstnameCheck = strlen($received['firstname']) >= 2;

                if($emailCheck && $passwordCheckLength && $repeatPasswordCheckLength && $passwordCheckEquality && $lastnameCheck && $firstnameCheck)
                {
                    if(count(User::where('email', $received['email'])->get()) == 0)
                    {
                        if($received['password'] == $received['repeatPassword'])
                        {
                            $id = $received['uniq_id'];
                            $received['password'] = password_hash($received['password'], PASSWORD_BCRYPT);
                            $received['created_at'] = time();
                            $received['rank'] = 1;
                            $received['uniq_id'] = $id;

                            $user = User::create($received);

                            $authToken['id'] = $user['id'];
                            $authToken['lastname'] = $user['lastname'];
                            $authToken['firstname'] = $user['firstname'];
                            $authToken['rank'] = $user['rank'];
                            $authToken['created_at'] = time();
                            $authToken['lifetime'] = 86400;
                            $authToken = JWT::encode($authToken);

                            $user->token = password_hash($authToken, PASSWORD_BCRYPT);
                            $user->save();

                            return json_encode(['success' => true, 'message' => $authToken, 'uniq_id' => $id]);
                        }else
                        {
                            return $this->forbidden('Les mots de passe ne sont pas identiques !');
                        }
                    }else
                    {
                        return $this->forbidden('Cette adresse email est déjà utilisée !');
                    }
                }else
                {
                    $message = '<p>Veuillez vérifier les points suivants:</p><ul id=\'errorsList\'>';
                    if(!$emailCheck) $message .= '<li>Votre adresse email est incorrecte.</li>';
                    if(!$passwordCheckLength && !$repeatPasswordCheckLength) $message .= '<li>Les mots de passe doivent comporter au moins 8 caractères</li>';
                    if(!$passwordCheckEquality) $message .= '<li>Les mots de passe ne sont pas identiques</li>';
                    if(!$lastnameCheck) $message .= '<li>Votre nom doit comporter au moins 2 caractères</li>';
                    if(!$firstnameCheck) $message .= '<li>Votre prénom doit comporter au moins 2 caractères</li>';
                    $message .= '</ul>';

                    return $this->forbidden($message);
                }
            }else
            {
                return $this->forbidden('Veuillez remplir tous les champs !');
            }
        }

        public function verify($token = null, $id = null)
        {
            if($token !== null) $_POST['token'] = $token;
            if($id !== null) $_POST['id'] = $id;

            if(!empty($_POST['token']) && !empty($_POST['id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $id = htmlspecialchars($_POST['id']);

                $checkTokenSignature = json_encode(JWT::check($token));

                if($checkTokenSignature == '"invalid"')
                {
                    LogManager::store('[AUTH] Détection d\'un token invalide. (ID utilisateur: '.$id.')', 2);
                    return $this->forbidden($checkTokenSignature);
                }

                if(count(User::where('uniq_id', $id)->get()) != 0)
                {
                    $user = User::where('uniq_id', $id)->first();

                    if(password_verify($token, $user['token']))
                    {
                        return json_encode(['success' => true, 'message' => $checkTokenSignature]);
                    }

                    LogManager::store('[AUTH] Le token du client ('.$id.') ne correspond pas à celui de la DB de '.$user['uniq_id'].'.', 2);
                    return $this->forbidden('"invalid"');
                }else
                {
                    LogManager::store('[AUTH] Le token client ne correspond à aucun uniq_id du serveur. (ID utilisateur: '.$id.')', 2);
                    return $this->forbidden('unknownId');
                }
            }else
            {
                return $this->forbidden('noToken');
            }
        }

        public function checkRegisterConfirmation()
        {
            $notEmpty = !empty($_POST['uniq_id']) &&
                        !empty($_POST['token']) && 
                        !empty($_POST['avatar']) && 
                        !empty($_POST['biography']) && 
                        !empty($_POST['sex']) && 
                        !empty($_POST['birthdate']) && 
                        !empty($_POST['relationshipstatus']) && 
                        !empty($_POST['job']) && 
                        !empty($_POST['birthplace']) && 
                        !empty($_POST['livingplace']);
            
            if($notEmpty)
            {
                $received = [
                    "uniq_id"  =>  htmlspecialchars($_POST['uniq_id']), 
                    "token"  =>  htmlspecialchars($_POST['token']),  
                    "avatar"  =>  htmlspecialchars($_POST['avatar']),
                    "biography"  =>  htmlspecialchars($_POST['biography']),
                    "sex" =>  htmlspecialchars($_POST['sex']),
                    "birthdate" =>  htmlspecialchars($_POST['birthdate']),
                    "relationshipstatus" =>  htmlspecialchars($_POST['relationshipstatus']),
                    "job" =>  htmlspecialchars($_POST['job']),
                    "birthplace" =>  htmlspecialchars($_POST['birthplace']),
                    "livingplace" =>  htmlspecialchars($_POST['livingplace'])
                ];

                $verify = json_decode($this->verify($received['token'], $received['uniq_id']));

                if($verify->success)
                {
                    $biographyCheckLength = strlen($received['biography']) >= 10;
                    $sexChoosenCheck = $received['sex'] == 'Homme' || $received['sex'] == 'Femme' || $received['sex'] == 'Autre';
                    $jobCheckLength = strlen($received['job']) >= 3;
                    $birthdateCheck = strlen($received['birthdate']) == 10;
                    $birthplaceCheckLength = strlen($received['birthplace']) >= 3;
                    $RelationshipstatusCheck = $received['relationshipstatus'] == 'Célibataire' || $received['relationshipstatus'] == 'En couple' || $received['relationshipstatus'] == 'Marié(e)' || $received['relationshipstatus'] == 'Veuf(ve)' || $received['relationshipstatus'] == 'Non précisé';
                    $livingPlaceCheckLength = strlen($received['livingplace']) >= 3;

                    if($biographyCheckLength && $sexChoosenCheck && $jobCheckLength && $birthdateCheck && $birthplaceCheckLength && $RelationshipstatusCheck && $livingPlaceCheckLength)
                    {
                        User::where('uniq_id', $received['uniq_id'])
                            ->update([
                                "firsttime" =>  0,  
                                "avatar" =>  $received['avatar'],
                                "biography" =>  $received['biography'],
                                "sex"  =>  $received['sex'],
                                "birthdate" =>  $received['birthdate'],
                                "relationshipstatus" =>  $received['relationshipstatus'],
                                "job" =>  $received['job'],
                                "birthplace"  =>  $received['birthplace'],
                                "livingplace" =>  $received['livingplace']
                            ]);

                        return json_encode(['success' => true]);

                    }else
                    {
                        $message = '';
                        if(!$biographyCheckLength) $message .= 'biographyCheckLength,';
                        if(!$sexChoosenCheck) $message .= 'sexChoosenCheck,';
                        if(!$jobCheckLength) $message .= 'jobCheckLength,';
                        if(!$birthdateCheck) $message .= 'birthdateCheck,';
                        if(!$birthplaceCheckLength) $message .= 'birthplaceCheckLength,';
                        if(!$RelationshipstatusCheck) $message .= 'RelationshipstatusCheck,';
                        if(!$livingPlaceCheckLength) $message .= 'livingPlaceCheckLength,';
                        return $this->forbidden('Veuillez remplir les champs correctement !'.$message);
                    }
                }
            }else
            {
                return $this->forbidden('Veuillez remplir tous les champs !');
            }
        }

        public function getFirstTime()
        {
            if(!empty($_POST['token']) && !empty($_POST['uniq_id']))
            {
                $token = htmlspecialchars($_POST['token']);
                $id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->verify($token, $id));
                
                if($verify->success)
                {
                    $user = User::where('uniq_id', $id)->first();
                    return json_encode(['success' => true, 'message' => $user['firsttime']]);
                }else
                {
                    return $this->forbidden('wrongToken');
                }
            }else
            {
                return $this->forbidden('noInfos');
            }
        }

        public function changePassword()
        {
            if (!empty($_POST['token']) && !empty($_POST['uniq_id'])) {
                $token = htmlspecialchars($_POST['token']);
                $uniq_id = htmlspecialchars($_POST['uniq_id']);

                $verify = json_decode($this->verify($token, $uniq_id));

                if ($verify->success) {
                    if(!empty($_POST['actualPassword']) && !empty($_POST['newPassword']))
                    {
                        $actualPassword = htmlspecialchars($_POST['actualPassword']);
                        $newPassword = htmlspecialchars($_POST['newPassword']);

                        if(strlen($actualPassword) >= 8 && strlen($newPassword) >= 8) {
                            $user = UserModel::where('uniq_id', $uniq_id)->first();

                            if(password_verify($actualPassword, $user->password)) {
                                $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
                                $user->save();
                                return json_encode(['success' => true]);
                            } else {
                                return $this->forbidden('incorrectPassword');
                            }
                        } else {
                            return $this->forbidden('invalidData');
                        }
                    } else {
                        return $this->forbidden('emptyData');
                    }
                }else
                {
                    LogManager::store('[POST] Tentative de modification du mot de passe de l\'utilisateur avec un token invalide (ID utilisateur: ' . $uniq_id . ')', 2);
                    return $this->forbidden('invalidToken');
                }
            }
        }
    }