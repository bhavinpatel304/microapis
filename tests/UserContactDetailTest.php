<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserContactDetailTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUserContactDetail()
    {
        $response = $this->call('POST', 'login', ['email'=>'jaypal.chauhann@emxcelsolutions.com', 'password'=>'jaypal@123']);
        $data = json_decode($response->getContent());
        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        $this->get('profile/contact-info', $headers);
        $this->seeStatusCode(200);
        $this->seeJsonStructure(['contact_persone_name', 'contact_email', 'contact_mobile_no']);
    }
}
