<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    public static $token = '';
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLogin()
    {
        $reqData = [
            'email' => 'admin@gmail.com',
            'password' => '123456'
        ];
        $this->json('POST', '/v2/users/login', $reqData)->seeJson(['status' => 'success']);

        $resultLogin = json_decode($this->response->getContent());
        self::$token = $resultLogin->result->token;
    }

//    public function testProfile()
//    {
//        $reqData = [
//            'email' => 'khoaht@canavi.vn',
//            'password' => 'datkhoa'
//        ];
//        $reqHeader = [
//            'Authorization' => "Bearer ".self::$token
//        ];
//        $this->json('GET', '/v2/users/profile', $reqData, $reqHeader)->seeJson(['status' => 'success']);
//    }
}
