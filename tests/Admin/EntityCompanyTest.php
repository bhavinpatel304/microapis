<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class EntityCompanyTest extends TestCase
{
    /**
     * how to Run function ==> ./vendor/bin/phpunit --filter testEntityAllRequestsCompany
     *
     * @return 
     */
    public function testEntityAllRequestsCompany()
    {
        $response = $this->call('POST', 'admin/login', ['email'=>'bhavin.patel@emxcelsolutions.com', 'password'=>'bhavin']);
        $data = json_decode($response->getContent());
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $this->get('admin/user/entity/company', $headers);
       
        $this->seeStatusCode(200);
    }
    
    
    
    public function testEntityRequestCompanyID(){
    
        $response = $this->call('POST', 'admin/login', ['email'=>'bhavin.patel@emxcelsolutions.com', 'password'=>'bhavin']);
        $data = json_decode($response->getContent());
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $this->get('admin/user/entity/company/73', $headers);
       
        $this->seeStatusCode(200);
    }
    
}
