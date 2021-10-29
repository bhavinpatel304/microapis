<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Auth;
use App\Traits\ApiResponser;
use GuzzleHttp\Client;
use Validator;
use App\Services\AccessLocationService;


class LocationController extends Controller {

    use ApiResponser;

    /**
     * The service to consume the Auth micro-service
     * 
     */
    public $accessLocationService;
    public function __construct(AccessLocationService $accessLocationService)
    {
        $this->accessLocationService = $accessLocationService;
        
    }
    
    public function getCountry(Request $request) {
         return $this->successResponse($this->accessLocationService->getCountryList($request->all()));
    }

    public function getState(Request $request) {
         return $this->successResponse($this->accessLocationService->getStateList($request->all()));
    }

    public function getCity(Request $request) {
       return $this->successResponse($this->accessLocationService->getCityList($request->all()));
    }
    
}
