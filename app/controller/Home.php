<?php

    namespace Controller;
    
    use \Kernel\JWT;

    class Home extends Controller
    {
        public function index()
        {
            $message = "Pas d'id";

            if(!empty($_GET['id'])) $message = "id: " . $_GET['id'];

            return json_encode(['success' => true, 'message' => $message]);
        }

        public function postit()
        {
            if(!empty($_POST['username']) && !empty($_POST['password']))
            {
                $received = [
                    'id' => '4u384$°&é!Jor881199',
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
                return $this->forbidden();
            }
        }
    }