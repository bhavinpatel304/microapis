<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\CompanyType;
use Auth;
use Storage;
use Illuminate\Support\Str;
use App\Traits\ApiResponser;
use App\Services\AuthApiService;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use App\Traits\MediaFiles;
use Illuminate\Support\Facades\Mail;
use App\Mail\MyEmail;
use App\Models\DocMaster;
use App\Services\SmsService;

class AuthController extends Controller
{
    use ApiResponser,MediaFiles;

    /**
    * The service to consume the Auth micro-service
    * 
    */

    public $authApiService;
    public function __construct(AuthApiService $authApiService)
    {
        $this->authApiService = $authApiService;
    }

    
    
    /**
    * Authenticate Admin user
    *
    * @return \Illuminate\Http\Response
    */
    public function adminAuthenticate(Request $request)
    {  
        $rules = [
            'email' => 'required', //|alpha_num|min:8|regex:/^[a-zA-Z].*/',
            'password' => 'required'
            ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }

        $userData = User::where('email', $request->input('email'))->where('user_type_id',1)->first();
        
        if(empty($userData)){
            return $this->errorResponse(trans('messages.mismatch_email_password'),401);
        }

        if($userData->status!=1 || empty($userData->email_verified_at) ){
            return $this->errorResponse(trans('messages.mismatch_email_password'),401);
        }

        $input = ['grant_type'=>'password',
            'username'=>$userData->email,
            'password'=>$request->password,
            'user_type'=> '1',
            'device_token'=>'123456789',
            'device_type'=>'web',
        ];
        try{
            $authToken = $this->successResponse($this->authApiService->apiAuthenticate($input));
        }catch(\Exception $e){
            if($e->getCode()==400){
                return $this->errorResponse(trans('messages.mismatch_email_password'),401);
            }
            return $this->errorResponse(trans('messages.unexpected'), Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        $authToken = json_decode($authToken->getContent());
       
        //get more detail of login user and update to our local db
        $headers = [
            'Authorization'=>"Bearer ".$authToken->access_token
        ];
        try{
            $response = $this->successResponse($this->authApiService->apiGetUser($authToken->data->userId, $headers));
            $output = json_decode($response->getContent());   
            $userData->fname=$output->data->fname;
            $userData->lname=$output->data->lname;
            $userData->update();
            $finalData = $userData;   
            
        }catch(\Exception $e){
           // dd($e->getMessage());
            $finalData = $userData;
            $finalData->access_token=$authToken->access_token;
            $finalData->token_type=$authToken->token_type;
            $finalData->refresh_token=$authToken->refresh_token;
            $finalData->expires_in=$authToken->expires_in;
        }
        $finalData = $userData;

        $userProfile = UserProfile::where('user_id',$finalData->id)->where('is_mobile_verified',1)->first();
        $finalData->access_token=$authToken->access_token;
        $finalData->access_token=$authToken->access_token;
        $finalData->token_type=$authToken->token_type;
        $finalData->refresh_token=$authToken->refresh_token;
        $finalData->expires_in=$authToken->expires_in;
        return $this->successResponse($finalData);
    }


    /**
    * Logout.
    *
    * @return \Illuminate\Http\Response
    */
    public function logout(Request $request)
    {   
        $token = ltrim($request->header('Authorization'),"Bearer");
        $token = trim($token);
        $response = json_decode($this->authApiService->apiUserLogout($token));
        if($response->code==1000){
            return $this->successResponse(['success'=>trans('messages.logout')]);
        }
        return $this->errorResponse('Unauthorized',401);
    }


    /**
    * Change Password.
    * @param \Illuminate\Http\Request
    * @return \Illuminate\Http\Response
    */
    public function changePassword(Request $request)
    {  
        dd('NOT IN USE');
        $this->validate($request, [
            'username' => 'required'
        ]);

        $authData = Auth::user();

        //make API call for get MailAuth Token
        $input = ['grant_type'=>'password',
            'username'=>config('services.api_auth.auth_base_user'),
            'password'=>config('services.api_auth.auth_base_pass')
        ];

        try{
            $mainAuthToken = $this->successResponse($this->authApiService->apiAuthenticate($input));
        }catch(\Exception $e){
            $errorCode = $e->getCode();
            return $this->errorResponse($e->getResponse()->getBody()->getContents(),$errorCode); 
        }

        $mainAuthToken = json_decode($mainAuthToken->getContent());

        $input = ['username'=>$authData->username,
            'old_password'=>$request->old_password,
            'password'=>$request->password
        ];

        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>"Bearer ".$mainAuthToken->access_token
        ];          

        try{
            return $this->successResponse($this->authApiService->apiChangePassword(json_encode($input), $headers));   
        }catch(\Exception $e){
            $errorCode = $e->getCode();
            return $this->errorResponse($e->getResponse()->getBody()->getContents(),$errorCode); 
        }

    }


    /**
    * Get refresh token 
    *
    * @return \Illuminate\Http\Response
    */
    public function refreshToken(Request $request)
    {  
        $this->validate($request, [
           'refresh_token' => 'required'
        ]);

        //make API call for get MailAuth Token
        $input = ['grant_type'=>'refresh_token',
            'refresh_token'=>$request->refresh_token
        ];
        return $this->successResponse($this->authApiService->apiAuthenticate($input));
    }


    /**
    * forget Password request
    *
    * @param email 
    * @return \Illuminate\Http\Response
    */
    public function forgetPassword(Request $request)
    {  
        $rules = [
            'email' => 'required|email'
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }

        if(!$this->isExists($request->email)){
            return $this->errorResponse(trans('messages.email_not_available'),404);
        }
        $input = [
            'value'=>$request->email,
            'type'=>'email',
            'flowType'=>'REST_URL',
            'clientId'=>config('services.api_auth.auth_client_user'),
            'resetUrl'=>config('services.admin.reset_forgot_password_url').'/'.$request->email,
        ]; 
        try{
            $response =json_decode($this->authApiService->apiForgetPassword($input));
        }catch(\Exception $e){
            return $this->errorResponse(trans('messages.email_not_available'),404);
        }
        
        if($response->code==1000){
            return $this->successResponse(['success'=>trans('messages.forgot_password_email_sent_success')]);
        }
        return $this->errorResponse(trans('messages.email_not_available'),404);
    }

    /**
    * reset user password request
    *
    * @param forget Password token 
    * @return \Illuminate\Http\Response
    */
    public function resetPassword(Request $request)
    { 
        $rules = [
            'email'=>'required|email',
            'forgot_password_token' => 'required|string',
            'password' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }

        $forgotPasswordToken = $request->forgot_password_token;
        $email = $request->email;
        $password = $request->password;

        if(!$this->isExists($request->email)){
            return $this->errorResponse(trans('messages.email_not_available'),404);
        }

        try{
            $forgotPasswordToken = base64_decode($forgotPasswordToken);
        }catch(\Exception $e){
            return $this->errorResponse(trans('messages.reset_password_token_invalid'),400);
        }
       
        
        //validate forgot password token 
        $input = [
            'value'=>$request->email,
            'type'=>'email',
            'otp'=>$forgotPasswordToken,
        ]; 
        try{
            $response =json_decode($this->authApiService->apiValidateForgetPassword($input));
        }catch(\Exception $e){
            return $this->errorResponse(trans('messages.reset_password_token_invalid'),400);
        }

        $input = ['forgot_password_token'=>$forgotPasswordToken,
            'password'=>$request->password
        ]; 
        $headers = ['Content-Type' => 'application/json'];
        try{
            $response = json_decode($this->authApiService->apiResetPassword(json_encode($input),$headers));
        }catch(\Exception $e){
            return $this->errorResponse(trans('messages.reset_password_token_invalid'),400);
        }
       
        if($response->code==1000){
           return $this->successResponse(['success'=>trans('messages.password_reset_success')]);
        }
        return $this->errorResponse(trans('messages.reset_password_token_invalid'),400);

    }

    

    /**
    * Check user 
    *
    * @param  username 
    * @return bool
    */
    public function isExists($username){
        $userData = User::where('email', $username)->where('user_type_id',1)->first();
        if(empty($userData)){
            return false;
        }
        return true;
    }



    /**
    * get user details 
    *
    * @param  username 
    * @return \Illuminate\Http\Response
    */
    public function getUserDetails($username){
        //make API call for get MainAuth Token
        $mainAuthToken = $this->getMainAuthToken();
        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>"Bearer ".$mainAuthToken->access_token
        ]; 

        return $this->successResponse($this->authApiService->apiGetUser($username,$headers));
    }


    /**
    * get Main Auth Token 
    *
    * @param  
    * @return Token object
    */
    public function getMainAuthToken(){
        $input = ['grant_type'=>'password',
            'username'=>config('services.api_auth.auth_base_user'),
            'password'=>config('services.api_auth.auth_base_pass')
        ];

        $mainAuthToken = $this->successResponse($this->authApiService->apiAuthenticate($input));
        return json_decode($mainAuthToken->getContent());

    }

  

    public function verifyEmail(Request $request){
        $rules = [
            'token' => 'required',
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }
        try{
            $id = decrypt($request->token);            
        }catch(\Exception $e){
            return $this->errorResponse('Invalid token.',400);
        }

        $user = User::where('id',$id)->whereNull('email_verified_at')->first();
        if(empty($user)){
            return $this->errorResponse('Verification link experied.',410);
        }
        $user->markEmailAsVerified();
        $user->status =1;
        $user->update(); 
        return $this->successResponse(['message'=>'Successfully verified. Please login to continue'],201);

    }

}    
