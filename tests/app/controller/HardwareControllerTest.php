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

            // Get temp
            $result = $hardwareController->getTemp();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(is_int($result['temperature']));
        }

        public function testGetCentralProcessingUnitUsage() : void
        {
            Kernel::bootEloquent();
            $hardwareController = new HardwareController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            $result = $hardwareController->getCPUUsage();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(is_double($result['cpu_usage']));
        }
    }