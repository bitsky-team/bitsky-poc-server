<?php

    namespace Controller;
    
    use \Kernel\JWT;

    class Auth extends Controller
    {
        public function login()
        {
            $notEmpty = !empty($_POST['username']) && !empty($_POST['password']);
            
            if($notEmpty)
            {
                $received = [
                    "username" => htmlspecialchars($_POST['username']),
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
                    "username" => htmlspecialchars($_POST['username']),
                    "password" => htmlspecialchars($_POST['password']),
                    "repeatPassword" => htmlspecialchars($_POST['repeatPassword']),
                    "lastname" => htmlspecialchars($_POST['lastname']),
                    "firstname" => htmlspecialchars($_POST['firstname'])
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
    }