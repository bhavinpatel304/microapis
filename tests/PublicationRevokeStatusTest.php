<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PublicationRevokeStatusTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPublicationRevokeStatus()
    {
        $response = $this->call('POST', 'login', ['email'=>'bhavin.patel@emxcelsolutions.com', 'password'=>'bhavin']);

        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $publicationData = [
            'news_id' => 1,
            'login_user_id' => 3
        ];
        
        $response = $this->json('POST', 'news/1/revoke', $publicationData, $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
}
