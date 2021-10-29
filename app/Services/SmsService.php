<?php

namespace App\Services;

use App\Traits\ConsumeExternalService;

class SmsService
{
    use ConsumeExternalService;

    /**
     * The base uri to consume authors service
     * @var string
     */
    public $baseUri;

    /**
     * Authorization secret to pass to author api
     * @var string
     */
    public $senderId;

    public function __construct()
    {   
        $this->baseUri = config('services.sms_api.base_uri');
        $this->senderId = config('services.sms_api.sender_id');
        $this->apiKey = config('services.sms_api.api_key');
    }

    /**
     * Send SMS
    */
    public function sendSMS($data)
    {   
        $data['method'] ='sms';
        $data['sender'] = $this->senderId;
        $queryString['api_key'] =   $this->apiKey;
        return json_decode($this->performRequest('POST', '',$data,[],'',[],$queryString));
        
    }

    

}