<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PublicationDetailTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPublicationDetail()
    {
        $response = $this->call('POST', 'login', ['email'=>'jaypal.chauhann@emxcelsolutions.com', 'password'=>'jaypal@123']);
        $data = json_decode($response->getContent());
        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $publicationData = [
            'publication_name' => 'Test',
            'category' => array(1),
            'registered_address' => 'Test',
            'registered_pincode' => '312601',
            'registered_state' => 1,
            'registered_city' => 1,
            'operating_address' => 'Test',
            'operating_pincode' => '312601',
            'operating_state' => 1,
            'operating_city' => 1,
        ];
        $this->json('POST', 'publication-detail', $publicationData, $headers);
        $this->seeStatusCode(200);
        $this->seeJsonStructure(['success']);
    }
}
