<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class CompanyDetailTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCompanyDetail()
    {
        $response = $this->call('POST', 'login', ['email'=>'jaypal.chauhann@emxcelsolutions.com', 'password'=>'jaypal@123']);
        $data = json_decode($response->getContent());
        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>'Bearer '.$data->access_token
        ]; 
        
        $publicationData = [
            "is_govt"=>1,
            "company_name" => "compnay 3",
            "company_type_id"=> 1,
            "registerd_company_address" => "Portblair",
            "registerd_company_pincode" => "380015",
            "registerd_company_state_id" => "32",
            "registerd_company_city_id" => "1",
            "operating_company_address" => "Portblair",
            "operating_company_pincode" => "380015",
            "operating_company_state_id" => "32",
            "operating_company_city_id" => "2"
        ];
        $response = $this->json('POST', 'company-detail', $publicationData, $headers);
        
        $response = $this->seeStatusCode(200)->seeJsonStructure(['success']);
    }
}
