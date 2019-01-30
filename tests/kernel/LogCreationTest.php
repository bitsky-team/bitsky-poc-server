<?php

    namespace Tests\Kernel;

    use PHPUnit\Framework\TestCase;
    use \Kernel\LogManager;

    class LogCreationTest extends TestCase
    {
        public function testLogCreated() : void
        {
            LogManager::store('Hello', 1);
            $file =  '/var/www/html/logs/' . date('d-m-Y');
            $this->assertTrue(file_exists($file));
        }

        public function testLogContainsTheRightLine() : void
        {
            $file =  '/var/www/html/logs/' . date('d-m-Y');

            $fileContent = file_get_contents($file);
            $fileLines = explode('\n', $fileContent);
            $lastFileLine = $fileLines[count($fileLines) - 1];

            $this->assertTrue(strpos($lastFileLine, 'Niveau 1 => Hello') !== false);

            unlink($file);
        }
    }