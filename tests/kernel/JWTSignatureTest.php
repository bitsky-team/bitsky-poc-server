<?php

    namespace Tests\Kernel;

    use PHPUnit\Framework\TestCase;
    use \Kernel\JWT;

    class JWTSignatureTest extends TestCase
    {
        public function testInvalidCase() : void 
        {
            $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6InRlc3QifQ.TeU_jmLs0FJIh3hOS3Mh8XKBEts2WcigkOmT1J-gnDk";
            $this->assertEquals('invalid', JWT::check($token));
        }

        public function testValidCase() : void
        {
            $data = ['username' => 'test'];
            $token = JWT::encode($data);
            $this->assertEquals(JWT::jsonDecode(json_encode($data)), JWT::check($token));
        }
    }