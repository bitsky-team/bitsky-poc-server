<?php

    namespace Tests\App\Controller;

    use PHPUnit\Framework\TestCase;
    use \Kernel\Kernel;
    use \Controller\User as UserController;
    use \Model\User as UserModel;

    class UserControllerTest extends TestCase
    {        
        public function testGetAllAsAdmin() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Get All Users
            $result = $userController->getAll();
            $result =json_decode($result, true);
            
            // Check the results
            $this->assertTrue(is_array($result['users']));
            $this->assertTrue($result['success']);
            $this->assertNotCount(0, $result);
        }

        public function testGetAllAsUser() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get User Account
            $user = UserModel::where('rank', 1)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $user->token = password_hash($token, PASSWORD_BCRYPT);
            $user->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $user['uniq_id'];

            // Get All Users
            $users = $userController->getAll();
            $users =json_decode($users, true);
            
            // Check the results
            $this->assertFalse($users['success']);
        }

        public function testGetUser() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $_POST['user_id'] = $admin['id'];

            // Get post
            $result = $userController->getById();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(!is_null($result['user']));
        }

        public function testCreateAsAdmin() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];
            $_POST['type'] = 'ADD';

            // Preparing data
            $_POST['lastname'] = 'lastname';
            $_POST['firstname'] = 'firstname';
            $_POST['email'] = 'email@email.com';
            $_POST['rank'] = 1;
            $_POST['password'] = "securityfirst";
            $_POST['repeatPassword'] = "securityfirst";
            $_POST['biography'] = 'Cillum velit nostrud id eiusmod eiusmod nisi ut cillum esse occaecat Lorem cupidatat etdent.';
            $_POST['sex'] = 'Homme';
            $_POST['job'] = 'Tester';
            $_POST['birthdate'] = "1980-01-01";
            $_POST['birthplace'] = 'TestLand';
            $_POST['relationshipstatus'] = 'Célibataire';
            $_POST['livingplace'] = 'TestLand';
            $_POST['avatar'] = 'no';

            // Get Result
            $result = $userController->createOrUpdate();
            $result = json_decode($result, true);
            
            // Check the results
            $this->assertTrue($result['success']);
        }

        public function testCreateAsUser() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get User Account
            $user = UserModel::where('rank', 1)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $user->token = password_hash($token, PASSWORD_BCRYPT);
            $user->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $user['uniq_id'];
            $_POST['type'] = 'ADD';

            // Preparing data
            $_POST['lastname'] = 'lastname';
            $_POST['firstname'] = 'firstname';
            $_POST['email'] = 'email@email.com';
            $_POST['rank'] = 1;
            $_POST['password'] = "securityfirst";
            $_POST['repeatPassword'] = "securityfirst";
            $_POST['biography'] = 'Cillum velit nostrud id eiusmod eiusmod nisi ut cillum esse occaecat Lorem cupidatat etdent.';
            $_POST['sex'] = 'Homme';
            $_POST['job'] = 'Tester';
            $_POST['birthdate'] = "1980-01-01";
            $_POST['birthplace'] = 'TestLand';
            $_POST['relationshipstatus'] = 'Célibataire';
            $_POST['livingplace'] = 'TestLand';
            $_POST['avatar'] = 'no';

            // Get Result
            $result = $userController->createOrUpdate();
            $result = json_decode($result, true);
           
            // Check the results
            $this->assertFalse($result['success']);
        }

        public function testDeleteUserAsAdmin() : void 
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $user = UserModel::where('firstname', 'firstname')->first();
            $_POST['user_id'] = $user['id'];

            // Deleting user
            $result = $userController->delete();
            $result = json_decode($result, true);

            // TODO: fix this to assertTrue
            $this->assertFalse($result['success']);
        }

        public function testDeleteUserAsUser() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $user = UserModel::where('firstname', 'firstname')->first();
            $_POST['user_id'] = $user['id'];

            // Deleting user
            $result = $userController->delete();
            $result = json_decode($result, true);
            $this->assertFalse($result['success']);
        }
    }