<?php

    namespace Tests\Kernel;

    use PHPUnit\Framework\TestCase;
    use \Kernel\Router;

    class RouterTest extends TestCase
    {
        public function testRedirectTo403WithoutPath() : void
        {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['route'] = 'home';
            $this->expectOutputString('{"success":false,"error":"403"}');
            Router::launch();
        }
    }