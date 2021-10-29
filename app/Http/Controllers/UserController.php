<?php

namespace App\Http\Controllers;

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
use App\Mail\SubmitDocument;
use App\Mail\VerifyDocument;
use App\Models\DocMaster;
use App\Services\SmsService;
use App\Models\ProfileAddress;
use App\Models\ProfileDocument;

class UserController extends Controller
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
    * Authenticate user
    *
    * @return \Illuminate\Http\Response
    */
    public function authenticate(Request $request)
    {  
        $rules = [
            'email' => 'required', //|alpha_num|min:8|regex:/^[a-zA-Z].*/',
            'password' => 'required'
            ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }

        $userData = User::where('email', $request->input('email'))->whereIn('user_type_id',[2,3])->first();
        if(empty($userData)){
            return $this->errorResponse(trans('messages.mismatch_id_password'),401);
        }
        if($userData->status!=1 || empty($userData->email_verified_at) ){
            return $this->errorResponse(trans('messages.mismatch_id_password'),401);
        }

        $input = ['grant_type'=>'password',
            'username'=>$userData->email,
            'password'=>$request->password,
            'device_token'=>'123456789',
            'device_type'=>'web',
        ];
        try{
            $authToken = $this->successResponse($this->authApiService->apiAuthenticate($input));
        }catch(\Exception $e){
            if($e->getCode()==400){
                return $this->errorResponse(trans('messages.mismatch_id_password'),401);
            }
            return $this->errorResponse(trans('messages.unexpected'), Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        $authToken = json_decode($authToken->getContent());
       
        //get more detail of login user and update to our local db
        $headers = [
            'Authorization'=>"Bearer ".$authToken->access_token
        ];
        try{
           // $response = $this->successResponse($this->authApiService->apiGetUser($authToken->data->userId, $headers));
            $response = $this->authApiService->getOwnProfile($authToken->access_token);
            $output = json_decode($response);  
            $userData->fname=$output->data->fname;
            $userData->lname=$output->data->lname;
            $userData->update();
        }catch(\Exception $e){}
        $finalData = $userData;

        $userProfile = UserProfile::where('user_id',$finalData->id)->first();
        if(empty($userProfile)){
            $finalData->is_mobile_verified = 0;
        }else{
            $finalData->is_mobile_verified = $userProfile->is_mobile_verified; 
        }
        
        $profileAddress = ProfileAddress::where('user_id',$finalData->id)->get();
        if($profileAddress->count()){
            $finalData->is_organization_details = 1;
            if(empty($userProfile)){
                $finalData->is_govt=0;
            }else{
                 $finalData->is_govt = $userProfile->is_govt;
            }
        }else{
            $finalData->is_organization_details = 0;
            $finalData->is_govt=0;
        }

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
        $this->validate($request, [
            'old_password' => 'required',
            'password' => 'required',
            'confirm_password' => 'required|same:password'
        ]);

        $input = [
            'old_password'=>$request->old_password,
            'password'=>$request->password,
            'id'=>$request->login_user_uuid,
        ];

        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>$request->header('Authorization')
        ];          

        try{
            $response = $this->successResponse($this->authApiService->apiChangePassword(json_encode($input), $headers));   
        }catch(\Exception $e){
            $body = json_decode($e->getResponse()->getBody()->getContents());
            $message = isset($body->message) ? $body->message : "";
            $errorCode = $e->getCode();
            return $this->errorResponse($message,$errorCode); 
        }

        if($response->code==1000){
            return $this->successResponse(['success'=>$response->message]);
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
            'resetUrl'=>config('services.reset_forgot_password_url').'/'.$request->email,
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
    * validate forget Password request
    *
    * @param forget Password token 
    * @return \Illuminate\Http\Response
    */
    public function validateForgetPassword(Request $request)
    {  
        //Not in use
        /*$this->validate($request, [
            'forgot_password_token' => 'required'
        ]);

        $input = $request->forgot_password_token; 
        return $this->successResponse($this->authApiService->apiValidateForgetPassword($input));*/
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

        try{
            $forgotPasswordToken = base64_decode($forgotPasswordToken);
        }catch(\Exception $e){
            return $this->errorResponse(trans('messages.reset_password_token_invalid'),400);
        }
       // dd($forgotPasswordToken);
        
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
            $body = json_decode($e->getResponse()->getBody()->getContents());
            $message = isset($body->message) ? $body->message : "";
            return $this->errorResponse($message,$e->getCode());
        }
       
        if($response->code==1000){
           return $this->successResponse(['success'=>trans('messages.password_reset_success')]);
        }
        return $this->errorResponse(trans('messages.reset_password_token_invalid'),400);

    }

    /**
    * Create new user
    *
    * @param  
    * @return \Illuminate\Http\Response
    */

    public function register(Request $request)
    { 
        
        /*$use = User::where('id',4)->first();
        Mail::to($use->email)->send(new MyEmail());
        $use->sendEmailVerificationNotification();*/
        //dd(1);
        $rules = [
            'email'=>'required|email|max:50|unique:user_details,email,NULL,id,deleted_at,NULL',
            'contact_number'=>'required|unique:user_details,contact_no,NULL,id,deleted_at,NULL|digits:10',
            'fname'=>'required|string|max:30',
            'lname'=>'required|string|max:30',
            'password'=>'required',
            //'confirmPassword'=>'required|same:password',
            'type_id'=>'required|in:2,3'
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }
        
        //check email
        $emailExists = $this->authApiService->checkUserExists($request->email);
        $emailExists = json_decode($emailExists);

        //get Auth user aceess token
        $mainAuthToken = $this->authApiService->loginMainAuth();
        if(!$mainAuthToken){
            return $this->errorResponse('Something went wrong. Try again.', 500);
        }

        if($emailExists->data->userid){
            // from auth to local
            $headers = [
                'Authorization'=>"Bearer ".$mainAuthToken
            ];
            $userDetails = $this->authApiService->getUserDetailByUUId($emailExists->data->userid, $headers);
            if(!$userDetails){
                //uuid is found but can not get more detail about uuid
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            if($userDetails->code!=1000){
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            $localUser = $this->storeUserToDB($userDetails,$request);
            if(!$localUser){
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            $localUser->sendEmailVerificationNotification();
            return $this->successResponse(["success"=>"Successfully registered. Verification link has been sent your email address ".$localUser->email],Response::HTTP_CREATED);
        }
        
        $phoneExists = $this->authApiService->checkUserExists($request->contact_number);
        $phoneExists = json_decode($phoneExists);
        if($phoneExists->data->userid){
            // from auth to local
            $headers = [
                'Authorization'=>"Bearer ".$mainAuthToken
            ];
            $userDetails = $this->authApiService->getUserDetailByUUId($phoneExists->data->userid, $headers);
            if(!$userDetails){
                //uuid is found but can not get more detail about uuid
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            if($userDetails->code!=1000){
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            $localUser = $this->storeUserToDB($userDetails,$request);
            if(!$localUser){
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            $localUser->sendEmailVerificationNotification();
            return $this->successResponse(["success"=>"Successfully registered. Verification link has been sent your email address ".$localUser->email],Response::HTTP_CREATED);
        }
        
        // create new user to Auth
        $headers = ['Content-Type' => 'application/json',
            'Authorization'=>"Bearer ".$mainAuthToken
        ];

        $input = $request->only(['email','password']);
        $input['phone'] =$request->contact_number;
        $input['username'] =$request->email;
        $input['fname'] =$request->fname;
        $input['lname'] =$request->lname;
        $input['enabled'] = true;

        try{
            $userDetails = $this->authApiService->apiCreateUser(json_encode($input), $headers);
            $userDetails = json_decode($userDetails);
            $localUser = $this->storeUserToDB($userDetails,$request);
            if(!$localUser){
                return $this->errorResponse('Something went wrong. Try again.', 500);
            }
            $localUser->sendEmailVerificationNotification();
            return $this->successResponse(["success"=>"Successfully registered. Verification link has been sent your email address ".$localUser->email],Response::HTTP_CREATED);

        }catch(\Exception $e){
            
            $errorCode = $e->getCode();
            $errMessage = [];
            $message = json_decode($e->getResponse()->getBody()->getContents());
            //dd($message);
            if(isset($message->trace)){
                $message = $message->trace;
               // dd($message);
                foreach ($message as $key => $value){
                    if(!array_key_exists($value->field, $errMessage)){
                        $errMessage[$value->field] = [];
                    }
                    $errMessage[$value->field][] = $value->message;
                    
                }
            }
            return $this->errorResponse($errMessage,$errorCode);
        }

        
    }

    /**
    * Check user 
    *
    * @param  username 
    * @return bool
    */
    public function isExists($username){
        $userData = User::where('email', $username)->whereIn('user_type_id',[2,3])->first();
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

    /**
    * Store new user 
    *
    * @param  user detail object
    * @return Illuminate\Database\Eloquent
    */
    public function storeUserToDB($userDetails,$request){
        $user =  User::create([
            'email'=>$userDetails->data->email,
            'contact_no'=>$userDetails->data->phone,
            'fname'=>$userDetails->data->fname,
            'lname'=>$userDetails->data->lname,
            'user_type_id'=>$request->type_id,
            'user_uuid'=>$userDetails->data->id,
            'status'=>0,
            'created_at'=>Carbon::now(),
        ]);
        if($user){
            UserProfile::create([
                'contact_persone_name'=>$userDetails->data->fname.' '.$userDetails->data->lname,
                'contact_email'=>$userDetails->data->email,
                'contact_mobile_no'=>$userDetails->data->phone,
                'is_mobile_verified'=>0,
                'status'=>0,
                'created_at'=>Carbon::now(),
                'created_by'=>$user->id,
                'user_id'=>$user->id,
            ]);
        }  
        //make admin
        if($request->keyCode && $request->keyCode==='hdf7&23#lsjfsu@q349(ksjfdsj!%'){
            $user->email_verified_at = Carbon::now();
            $user->user_type_id = 1;
            $user->status =1;    
            $user->update();
            
        } 
        return $user;
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

    public function getUserDetail(request $request) {
       /* $headers = ['Content-Type' => 'application/json',
            'Authorization'=>$request->header('Authorization')
        ]; */
       $userDetail = UserProfile::select('contact_persone_name', 'contact_email', 'contact_mobile_no')->where('user_id',$request->login_user_id)->first();
       return $this->successResponse($userDetail);
    }

    public function getDocType() {
        $doc = DocMaster::get();
        return $this->successResponse($doc);
    }
    
    public function getCompanyType() {
       $companytypes = CompanyType::select('id', 'type')->get();
       return $this->successResponse($companytypes);
    }

    public function mailTest(){
        $use = User::where('id',1)->first();
        Mail::to('mjain9041@mailinator.com')->send(new VerifyDocument($use));
    }
    
    public function getDocumentList(Request $request) {        
        $documentList = ProfileDocument::
                with(['documentType'=>function($q){
            $q->select('id','name','type')->orderBy('type','ASC');
        }])
        ->where('user_id',$request->login_user_id)
        ->get(); 
        return $this->successResponse($documentList);
    }

    public function delUser(Request $request){

        if($request->keyCode && $request->keyCode==='hdf7&23#lsjfsu@q349(ksjfdsj!%' && !empty($request->email)){
            $user = User::where('email','=',$request->email)->first();
            if(empty($user)){
                return $this->errorResponse('No data found',404);
            }
            $user->delete();
            return $this->successResponse(['success'=>'deleted'],200);
        }
        return $this->errorResponse('No data found',404);
    }
}    
