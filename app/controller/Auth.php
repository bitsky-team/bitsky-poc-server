<?php

    namespace Controller;
    
    use \Kernel\JWT;
    use \Model\User;

    class Auth extends Controller
    {
        public function login()
        {
            $notEmpty = !empty($_POST['username']) && !empty($_POST['password']);
            
            if($notEmpty)
            {
                $received = [
                    "email" => htmlspecialchars($_POST['email']),
                    "password" => htmlspecialchars($_POST['password'])
                ];

                $data = $received;
                unset($data['password']);
                
                $auth_token = JWT::encode($data);

                return json_encode([
                    'success' => true, 
                    'received' => $received, 
                    'auth_token' => $auth_token,
                    'translated_token' => JWT::check($auth_token)
                ]);                
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
                    "uniq_id"           =>  md5(uniqid()),
                    "email"             =>  htmlspecialchars($_POST['email']),
                    "password"          =>  htmlspecialchars($_POST['password']),
                    "repeatPassword"    =>  htmlspecialchars($_POST['repeatPassword']),
                    "lastname"          =>  htmlspecialchars($_POST['lastname']),
                    "firstname"         =>  htmlspecialchars($_POST['firstname'])
                ];

                $emailCheck = preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $received['email']);
                $passwordCheckLength = strlen($received['password']) >= 8;
                $repeatPasswordCheckLength = strlen($received['repeatPassword']) >= 8;
                $passwordCheckEquality = $received['password'] === $received['repeatPassword'];
                $lastnameCheck = strlen($received['lastname']) >= 2;
                $firstnameCheck = strlen($received['firstname']) >= 2;

                if($emailCheck && $passwordCheckLength && $repeatPasswordCheckLength && $passwordCheckEquality && $lastnameCheck && $firstnameCheck)
                {
                    if(count(User::where('email', $received['email'])->get()) == 0)
                    {
                        if($received['password'] == $received['repeatPassword'])
                        {
                            unset($received['repeatPassword']);
                            $received['password'] = password_hash($received['password'], PASSWORD_BCRYPT);
                            User::create($received);
                            return json_encode(['success' => true]);
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
                    if(!$emailCheck) $message .= '<li>Votre adresse email est incorrecte</li>';
                    if(!$passwordCheckLength && !$repeatPasswordCheckLength) $message .= '<li>Les mots de passe doivent comporter au moins 8 caractères</li>';
                    if(!$passwordCheckEquality) $message .= '<li>Les mots de passe ne sont pas identiques</li>';
                    if(!$lastnameCheck) $message .= '<li>Votre nom doit comporter au moins 2 caractères</li>';
                    if(!$firstnameCheck) $message .= '<li>Votre prénom doit comporter au moins 2 caractères</li>';
                    $message .= '</ul>';

                    return $this->forbidden($message);
                }

                /*

                $data = $received;
                unset($data['password']);
                
                $auth_token = JWT::encode($data);

                return json_encode([
                    'success' => true, 
                    'received' => $received, 
                    'auth_token' => $auth_token,
                    'translated_token' => JWT::check($auth_token)
                ]);*/
            }else
            {
                return $this->forbidden('Veuillez remplir tous les champs !');
            }
        }
    }