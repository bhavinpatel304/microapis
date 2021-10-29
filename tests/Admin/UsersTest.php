<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UsersTest extends TestCase
{
    /**
     * how to Run function ==> ./vendor/bin/phpunit --filter testGenerateOTP
     *
     * @return 
     */
    public function testGenerateOTP()
    {
        $$response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ];
        
        $response = $this->json('POST', 'generateotp', [], $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function testVerifyOTP()
    {
        $$response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ];
        
        $response = $this->json('POST', 'verifyotp', [], $headers);
 
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    public function countryTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
         $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
         
        $this->get('country', $headers);    
        
        $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
     public function stateTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
         $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
         
        $this->get('state', $headers);    
        
        $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
     public function cityTest()
    {
        
        $response = $this->call('POST', 'login', ['email'=>'beepnsay-publication@emxcelsolutions.com', 'password'=>'11111111']);
        $data = json_decode($response->getContent());
        
         $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
         
        $this->get('city', $headers);    
        
        $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
    
    
    
}
