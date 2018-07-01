<?php

    namespace Tests;

    use PHPUnit\Framework\TestCase;

    class TokenCheckerTest extends TestCase
    {
        public function testInvalidCase() : void
        {
            $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6InRlc3QifQ.TeU_jmLs0FJIh3hOS3Mh8XKBEts2WcigkOmT1J-gnDk";
            $this->assertEquals('invalid', \Kernel\JWT::check($token));
        }

        public function testValidCase() : void
        {
            $data = ['username' => 'test'];
            $token = \Kernel\JWT::encode($data);
            $this->assertEquals(\Kernel\JWT::jsonDecode(json_encode($data)), \Kernel\JWT::check($token));
        }
    }