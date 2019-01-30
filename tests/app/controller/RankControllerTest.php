<?php

    namespace Tests\App\Controller;

    use PHPUnit\Framework\TestCase;
    use \Kernel\Kernel;
    use \Controller\Rank as RankController;
    use \Model\Rank as RankModel;

    class RankControllerTest extends TestCase
    {        
        public function testGetAll() : void
        {
            Kernel::bootEloquent();
            $rankController = new RankController();

            // Get Ranks
            $ranks = $rankController->getAll();
            $result = json_decode($ranks, true);

            $this->assertTrue(is_array($result));
            $this->assertTrue($result['success']);
            $this->assertNotCount(0, $result);
        }
    }