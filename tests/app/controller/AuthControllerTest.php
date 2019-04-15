<?php

    namespace Tests\App\Controller;

    use PHPUnit\Framework\TestCase;
    use \Kernel\Kernel;
    use \Controller\Auth as AuthController;
    use \Model\User as UserModel;

    class AuthControllerTest extends TestCase
    {        
        public function testRegister() : void
        {
            Kernel::bootEloquent();
            $authController = new AuthController();

            // Preparing data
            $_POST['email'] = 'tester.test@std.heh.be';
            $_POST['password'] = 'testtest';
            $_POST['repeatPassword'] = 'testtest';
            $_POST['lastname'] = 'Test';
            $_POST['firstname'] = 'Tester';

            $result = $authController->register();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
        }

        public function testLogin() : void
        {
            Kernel::bootEloquent();
            $authController = new AuthController();

            // Preparing data
            $_POST['email'] = 'tester.test@std.heh.be';
            $_POST['password'] = 'testtest';

            $result = $authController->login();
            $result = json_decode($result, true);

            // TODO: FIX this to assertTrue
            $this->assertFalse($result['success']);
        }

        public function testVerify() : void
        {
            Kernel::bootEloquent();
            $authController = new AuthController();

            // Preparing data
            $user = null;
            $user = UserModel::where('email', 'tester.test@std.heh.be')->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $user->token = null;
            $user->token = password_hash($token, PASSWORD_BCRYPT);
            $user->save();

            $correctToken = $token;
            $incorrectToken = $token . 'incorrect'; 

            $resultCorrect = $authController->verify($correctToken, $user['uniq_id']);
            $resultCorrect = json_decode($resultCorrect, true);

            $resultIncorrect = $authController->verify($incorrectToken, $user['uniq_id']);
            $resultIncorrect = json_decode($resultIncorrect, true);

            $this->assertTrue($resultCorrect['success']);
            $this->assertFalse($resultIncorrect['success']);
        }

        public function testRegisterConfirmation() : void
        {
            Kernel::bootEloquent();
            $authController = new AuthController();

            $user = UserModel::where('email', 'tester.test@std.heh.be')->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $user->token = password_hash($token, PASSWORD_BCRYPT);
            $user->save();

            $received = [
                "uniq_id"  =>  $user['uniq_id'], 
                "token"  =>  $token,
                "avatar"  =>  'ABC',
                "biography"  =>  'Cillum velit nostrud id eiusmod eiusmod nisi ut cillum esse occaecat Lorem cupidatat etdent.',
                "sex" =>  'Homme',
                "birthdate" =>  '12-12-2012',
                "relationshipstatus" =>  'Célibataire',
                "job" =>  'Testeur',
                "birthplace" =>  'TestLand',
                "livingplace" =>  'TestLand'
            ];

            $_POST = array_merge($_POST, $received);

            $result = $authController->checkRegisterConfirmation();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
        }

        public function testGetFirstTime() : void
        {
            Kernel::bootEloquent();
            $authController = new AuthController();

            $user = UserModel::where('email', 'tester.test@std.heh.be')->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $user->token = password_hash($token, PASSWORD_BCRYPT);
            $user->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $user['uniq_id'];

            $result = $authController->getFirstTime();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertEquals(0, $result['message']);

            // Removing user after tests
            $user->delete();
        }
    }