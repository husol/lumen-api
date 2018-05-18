<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->get('/');

        $expectedResult = "Welcome to Canavi API. It's deployed with ".$this->app->version();

        $this->assertEquals($expectedResult, $this->response->getContent());
    }
}
