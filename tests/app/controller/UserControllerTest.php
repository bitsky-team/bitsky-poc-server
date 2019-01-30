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
            $_POST['token'] = $admin['token'];
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
            $_POST['token'] = $user['token'];
            $_POST['uniq_id'] = $user['uniq_id'];

            // Get All Users
            $users = $userController->getAll();
            $users =json_decode($users, true);
            
            // Check the results
            $this->assertFalse($users['success']);
        }

        public function testCreateAsAdmin() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $_POST['lastname'] = 'lastname';
            $_POST['firstname'] = 'firstname';
            $_POST['email'] = 'email@email.com';
            $_POST['rank'] = 1;
            $_POST['password'] = "securityfirst";
            $_POST['repeatPassword'] = "securityfirst";
            $_POST['biography'] = 'Cillum velit nostrud id eiusmod eiusmod nisi ut cillum esse occaecat Lorem cupidatat et. Ipsum Lorem veniam voluptate sint dolor consectetur non in consequat ea esse laborum cupidatat eu. Ea excepteur consectetur excepteur aute et consectetur. Lorem aute ad cillum commodo qui incididunt officia est laborum tempor occaecat ullamco labore. Incididunt adipisicing proident ex cillum sunt culpa commodo Lorem quis cillum ullamco reprehenderit. Cillum elit ullamco tempor sunt adipisicing consectetur velit ex.';
            $_POST['sex'] = 'Homme';
            $_POST['job'] = 'Tester';
            $_POST['birthdate'] = "0000-00-00";
            $_POST['birthplace'] = 'TestLand';
            $_POST['relationshipstatus'] = 'Célibataire';
            $_POST['livingplace'] = 'TestLand';
            $_POST['avatar'] = 'no';

            // Get Result
            $result = $userController->create();
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
            $_POST['token'] = $user['token'];
            $_POST['uniq_id'] = $user['uniq_id'];

            // Preparing data
            $_POST['lastname'] = 'lastname';
            $_POST['firstname'] = 'firstname';
            $_POST['email'] = 'email@email.com';
            $_POST['rank'] = 1;
            $_POST['password'] = "securityfirst";
            $_POST['repeatPassword'] = "securityfirst";
            $_POST['biography'] = 'Cillum velit nostrud id eiusmod eiusmod nisi ut cillum esse occaecat Lorem cupidatat et. Ipsum Lorem veniam voluptate sint dolor consectetur non in consequat ea esse laborum cupidatat eu. Ea excepteur consectetur excepteur aute et consectetur. Lorem aute ad cillum commodo qui incididunt officia est laborum tempor occaecat ullamco labore. Incididunt adipisicing proident ex cillum sunt culpa commodo Lorem quis cillum ullamco reprehenderit. Cillum elit ullamco tempor sunt adipisicing consectetur velit ex.';
            $_POST['sex'] = 'Homme';
            $_POST['job'] = 'Tester';
            $_POST['birthdate'] = "0000-00-00";
            $_POST['birthplace'] = 'TestLand';
            $_POST['relationshipstatus'] = 'Célibataire';
            $_POST['livingplace'] = 'TestLand';
            $_POST['avatar'] = 'no';

            // Get Result
            $result = $userController->create();
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
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $user = UserModel::where('firstname', 'firstname')->first();
            $_POST['user_id'] = $user['id'];

            // Deleting user
            $result = $userController->delete();
            $result = json_decode($result, true);
            $this->assertTrue($result['success']);
        }

        public function testDeleteUserAsUser() : void
        {
            Kernel::bootEloquent();
            $userController = new UserController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
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