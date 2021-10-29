<?php

namespace App\Services;

use App\Traits\ConsumeExternalService;

class AuthApiService
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
        $this->baseUri = config('services.api_auth.base_uri');
        $this->secret = config('services.api_auth.secret');
    }


    /**
     * User Login 
     */
    public function apiAuthenticate($data)
    {   
        $headers['Authorization'] = $this->secret; 
        return $this->performRequest('POST', 'oauth/token', $data,$headers);
    }

    /**
     * Get Login User Detail
     */
    public function apiGetUser($data,$headers=[])
    {   
        return $this->performRequest('GET', 'users/'.$data,[],$headers);
    }

    /**
     * Change Password
     */
    public function apiChangePassword($data,$headers=[])
    {   
        return $this->performRequest('PUT', 'users/changePassword', [], $headers, $data);
    }
    
    /**
     * Logout User
     */
    public function apiUserLogout($data)
    {   
        $headers['Authorization'] = $this->secret;
        $queryString = [
    		'token'=>$data
    	];
        return $this->performRequest('DELETE', 'oauth/token',[], $headers,'',[],$queryString);
    }


    /**
     * User Forgetpassword
     */
    public function apiForgetPassword($data)
    {   //dd($data);
        $headers['Authorization'] = $this->secret;
        return $this->performRequest('POST', 'forgotPassword', $data,$headers);
    }

    /**
     * validate Forgetpassword Token to mail Auth
     */
    public function apiValidateForgetPassword($data)
    {   
        $headers['Authorization'] = $this->secret;
    	$queryString = $data;
        return $this->performRequest('GET', 'forgotPassword/validate',[],$headers,'',[],$queryString); 
    }

    /**
     * reset password
     */
    public function apiResetPassword($data,$headers=[])
    {   
       return $this->performRequest('POST', 'password/reset', [], $headers ,$data);
    }


    /**
     * check user exist
     * param username
     * return bool
     */
    public function checkUserExists($data)
    {   
        return $this->performRequest('GET', 'users/check/'.$data);
    }


    /**
     * Create new User
     */
    public function apiCreateUser($data,$headers=[])
    {   
        return $this->performRequest('POST', 'users', [], $headers, $data);
    }

    /**
     * Update User
     */
    public function apiUpdateUser($data,$headers=[],$userId)
    {   
        return $this->performRequest('PUT', 'users/'.$userId, [], $headers, $data);
    }

    public function apiGetUserDetail($data, $headers)
    {   
        return $this->performRequest('GET', 'users/'.$data, [], $headers);
    }

    public function checkAuthTokenDetail($data){
        $headers['Authorization'] = $this->secret;
        $queryString = ['token'=>$data];
        return $this->performRequest('POST','oauth/check_token',[], $headers, '',[], $queryString);
    }

    /**
     * login suoer admin from cas
     * param 
     * return bool / accessToken
     */
    public function loginMainAuth(){
        //Super user
        $input = ['grant_type'=>'password',
            'username'=>config('services.api_auth.auth_base_user'),
            'password'=>config('services.api_auth.auth_base_pass'),
            'device_token'=>'123456789',
            'device_type'=>'web',
        ];
        try{
            $authToken = json_decode($this->apiAuthenticate($input));
            return $authToken->data->access_token;
        }catch(\Exception $e){
            return false; 
        }
    }



    /**
     * get user detail by uuid
     * header param acceess token of Main Auth as array
     * param uuid
     * return bool / user detail object
     */
    public function getUserDetailByUUId($data,$headers=[]){
        try{
            $response =  $this->performRequest('GET', 'users/'.$data,[],$headers);
            return json_decode($response);
        }catch(\Exception $e){
            return false;   
        }  
    }

    /**
     * get own profile detail by access token
     * header param acceess token
     * param access token
     * return bool / user detail object
     */
    public function getOwnProfile($data,$headers=[]){
        $headers = [
            'Authorization'=>"Bearer ".$data
        ];
        try{
            $response =  $this->performRequest('GET', 'users/profile/'.$data,[],$headers);
            return $response;
        }catch(\Exception $e){
            return false;   
        }  
    }


}