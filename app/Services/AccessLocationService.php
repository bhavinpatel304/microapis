<?php

namespace App\Services;

use App\Traits\ConsumeExternalService;
use Illuminate\Http\Request;

class AccessLocationService
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
    public $secret;

    public function __construct()
    {   
        $this->baseUri = config('services.location.base_uri');
        $this->secret = config('services.location.secret');
    }

    /**
     * get country list
     */
    public function getCountryList($data)
    {   
        return $this->performRequest('GET', 'getcountrylist', [], [], '', [], $data);
    }

    /**
     * get country list
     */
    public function getStateList($data)
    {   
        return $this->performRequest('GET', 'getstatelist', [], [], '', [], $data);
    }

   /**
     * get country list
     */
    public function getCityList($data)
    {   
        return $this->performRequest('GET', 'getcitylist', [], [], '', [], $data);
    }

}