<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class CompanyChatTest extends TestCase
{
    /*
    * how to Run function ==> ./vendor/bin/phpunit --filter chatCompanyListTest
    */
    public function chatCompanyListTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
         $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
         
        $this->get('chat/company', $headers);    
        
        $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function chatCompanyByIDTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
         $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
         
        $this->get('chat/company/1', $headers);        
        $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function chatCompanyRecentTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
         $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
         
        $this->get('chat/recent-company', $headers);        
        $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }

}
