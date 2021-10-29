<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PublicationNewsStatusTest extends TestCase
{
    /*
    * how to Run function ==> ./vendor/bin/phpunit --filter testPublicationAcceptStatusTest
    */
    public function testPublicationAcceptStatusTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $response = $this->json('POST', 'news/12/accept', [], $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function testPublicationRevokeStatusTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $response = $this->json('POST', 'news/12/revoke', [], $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function testPublicationRejectStatusTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $response = $this->json('POST', 'news/12/reject', [], $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function testPublicationReadyforpublishStatusTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $response = $this->json('POST', 'news/12/readyforpublish', [], $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
}