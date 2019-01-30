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

            $this->assertTrue($result['success']);
        }

        public function testVerify() : void
        {
            Kernel::bootEloquent();
            $authController = new AuthController();

            // Preparing data
            $user = UserModel::where('email', 'tester.test@std.heh.be')->first();
            $correctToken = $user['token'];
            $incorrectToken = $user['token'] . 'incorrect';

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

            $received = [
                "uniq_id"  =>  $user['uniq_id'], 
                "token"  =>  $user['token'],  
                "avatar"  =>  'ABC',
                "biography"  =>  'Occaecat fugiat commodo consectetur Lorem cupidatat nisi sit est et ullamco esse. Ex tempor aute nulla incididunt labore veniam reprehenderit laborum ullamco. Voluptate Lorem voluptate sint cillum ullamco. Ut anim cupidatat qui duis nulla anim id quis id ea irure ullamco. Qui Lorem ullamco non culpa sunt ipsum labore culpa labore excepteur cupidatat pariatur est. Nisi mollit veniam quis voluptate quis dolor dolore voluptate. Laboris ut irure ex eiusmod.',
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

            $_POST['token'] = $user['token'];
            $_POST['uniq_id'] = $user['uniq_id'];

            $result = $authController->getFirstTime();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertEquals(0, $result['message']);

            // Removing user after tests
            $user->delete();
        }
    }