<?php

    namespace Tests\App\Controller;

    use PHPUnit\Framework\TestCase;
    use \Kernel\Kernel;
    use \Controller\Hardware as HardwareController;
    use \Model\User as UserModel;

    class HardwareControllerTest extends TestCase
    {        
        public function testGetTemperature() : void
        {
            Kernel::bootEloquent();
            $hardwareController = new HardwareController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Get posts
            $result = $hardwareController->getTemp();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(is_int($result['temperature']));
        }
    }