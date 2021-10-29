<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\PublicationCategory;
use App\Models\UserProfileHistory;
use App\Models\ProfileAddress;
use Auth;
use DB;
use Storage;
use App\Services\AuthApiService;
use App\Traits\ApiResponser;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use App\Traits\MediaFiles;
use App\Models\ProfileDocument;
use App\Services\SmsService;
use App\Models\CategoryMaster;
use App\Mail\SubmitDocument;
use Illuminate\Support\Facades\Mail;
class UserProfileController extends Controller {

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

    public function generateOTP(Request $request) {
        $login_user_id = $request->login_user_id;
        $rules = [
            'contact_person_name' => 'required',
            //'contact_mobile_no' => 'required|digits:10',
            'contact_mobile_no' => 'required|unique:user_details,contact_no,'.$login_user_id.',id,deleted_at,NULL|digits:10',
        ];

        
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 400);
        }
        
        try {
            DB::beginTransaction();

            $userData = UserProfile::where('user_id',$login_user_id)->first();            
            if(empty($userData)){
                return $this->errorResponse(trans('messages.not_found'),404);
            }
            

            //make call to auth api
            $phoneExists = $this->authApiService->checkUserExists($request->contact_mobile_no);
            $phoneExists = json_decode($phoneExists);
            if($phoneExists->data->userid){
                if($request->login_user_uuid != $phoneExists->data->userid){
                    return $this->errorResponse('This contact number already linked to another account.',422);
                }
            }

            //dd(1);
            $otp = mt_rand(100000,999999);           
            $userData->mobile_otp = $otp;
            // $userData->is_mobile_verified = 0;
            $userData->updated_by = $login_user_id;
            $userData->updated_at = Carbon::now();
            $userData->update();
            
            UserProfileHistory::create([                
                'gen_user_id' => empty($userData->gen_user_id) ? '' : $userData->gen_user_id,
                'organization_name' => empty($userData->organization_name) ? '' : $userData->organization_name,
                'description' => empty($userData->description) ? '' : $userData->description,
                'logo_path' => empty($userData->logo_path) ? '' : $userData->logo_path,
                'company_type_id' =>  empty($userData->company_type_id) ? '' : $userData->company_type_id,
                'website' => empty($userData->website) ? '' : $userData->website,
                'is_govt' => empty($userData->is_govt) ? 0 : $userData->is_govt,               
                'contact_email' => empty($userData->contact_email) ? '' : $userData->contact_email,            
                'status' => empty($userData->status) ? 0 : $userData->status,
                'crud_type' => 'add',
                
                'user_id' => $userData->user_id,
                'contact_persone_name' => $userData->contact_persone_name,
                'contact_mobile_no' => $userData->contact_mobile_no,
                'is_mobile_verified' =>  $userData->is_mobile_verified,
                'mobile_otp' => $userData->mobile_otp,
                'created_at' => Carbon::now(),
                'created_by' => $login_user_id,
                'updated_by' => $login_user_id,
                'updated_at' => Carbon::now(),
            ]);
            
            DB::commit();
            $otpMessage = "Your One-time password is ".$otp;

            $smsService = new SmsService();
            try{
                $smsResponse = $smsService->sendSMS(['to'=>$request->contact_mobile_no,'message'=>$otpMessage]);
                if(isset($smsResponse->status) && $smsResponse->status=='OK' && isset($smsResponse->data[0]->status) && $smsResponse->data[0]->status=='AWAITED-DLR' ){
                    return $this->successResponse(['success'=>trans('messages.otp_sent_success')]);
                }else{
                     return $this->errorResponse(trans('messages.otp_send_failed'),502);
                }
            }catch(\Exception $e){
                return $this->errorResponse(trans('messages.otp_send_failed'),502);
            }

        } catch (\Exception $e) {
           return $this->errorResponse(trans('messages.error_internal'), 400);
        }
    }
    
    public function verifyOTP(Request $request) {
        $login_user_id = $request->login_user_id;
        $rules = [
            'contact_person_name' => 'required',
            //'contact_mobile_no' => 'required|digits:10',
            'contact_mobile_no' => 'required|unique:user_details,contact_no,'.$login_user_id.',id,deleted_at,NULL|digits:10',
            'otp' => 'required'
        ];

        
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 400);
        }

        $userData = UserProfile::where('user_id',$login_user_id)->first();
        if(empty($userData)){
           return $this->errorResponse(trans('messages.not_found'),404);
        }

        //make call to auth api
        $phoneExists = $this->authApiService->checkUserExists($request->contact_mobile_no);
        $phoneExists = json_decode($phoneExists);
        if($phoneExists->data->userid){
            if($request->login_user_uuid != $phoneExists->data->userid){
                return $this->errorResponse('This contact number already linked to another account.',422);
            }
        }
        if ($userData->mobile_otp != $request->otp) {
            return $this->errorResponse(trans('messages.otp_not_match'),401);
        }

        try {
            DB::beginTransaction();
            $userData->contact_persone_name = $request->contact_person_name;
            $userData->contact_mobile_no = $request->contact_mobile_no;
            $userData->mobile_otp='';
            $userData->is_mobile_verified = 1;
            $userData->updated_by = $login_user_id;
            $userData->updated_at = Carbon::now();
            $userData->update();
            
            User::where('id',$login_user_id)->update([
                'contact_no'=>$userData->contact_mobile_no,
                'updated_at'=>Carbon::now(),
            ]);
            UserProfileHistory::create([
                'gen_user_id' => empty($userData->gen_user_id) ? '' : $userData->gen_user_id,
                'organization_name' => empty($userData->organization_name) ? '' : $userData->organization_name,
                'description' => empty($userData->description) ? '' : $userData->description,
                'logo_path' => empty($userData->logo_path) ? '' : $userData->logo_path,
                'company_type_id' =>  empty($userData->company_type_id) ? '' : $userData->company_type_id,
                'website' => empty($userData->website) ? '' : $userData->website,
                'is_govt' => empty($userData->is_govt) ? 0 : $userData->is_govt,               
                'contact_email' => empty($userData->contact_email) ? '' : $userData->contact_email,            
                'status' => empty($userData->status) ? 0 : $userData->status,
                'crud_type' => 'add',
                
                'user_id' => $userData->user_id,
                'contact_persone_name' => $userData->contact_persone_name,
                'contact_mobile_no' => $userData->contact_mobile_no,
                'is_mobile_verified' =>  $userData->is_mobile_verified,
                'mobile_otp' => $userData->mobile_otp,
                'created_at' => Carbon::now(),
                'created_by' => $login_user_id,
                'updated_by' => $login_user_id,
                'updated_at' => Carbon::now(),
            ]);
            try{
                $token = ltrim($request->header('Authorization'),"Bearer");
                $token = trim($token);
                $headers = [
                    'Authorization'=>"Bearer ".$token,
                    'Content-Type'=>'application/json',
                ];
                $data= [
                    'phone'=> $userData->contact_mobile_no,
                ];
                $data = json_encode($data);    
                $updateSSO = $this->authApiService->apiUpdateUser($data,$headers,$request->login_user_uuid);

            }catch(\Exception $e){
                return $this->errorResponse('can not update. Please try again', 500);
            }
            
            DB::commit();
            $res = ["success"=>trans('messages.otp_verified')];
            return $this->successResponse($res);
        } catch (\Exception $e) {
            return $this->errorResponse(trans('messages.error_internal'), 400);            
        }
    }
    
    public function storeOrganizationDetails(Request $request)
    {
        try 
        {
            
            $login_user_id = $request->login_user_id;
            $rules = [
                'company_name' => 'required|max:30',                
                'is_govt' => 'required',
                'company_type_id' => 'exclude_if:is_govt,1|required',
                'registerd_company_address' => 'required|max:128',
                'registerd_company_pincode' => 'required',
                'registerd_company_state_id' => 'required',
                'registerd_company_city_id' => 'required',
                'operating_company_address' => 'required|max:128',
                'operating_company_pincode' => 'required',
                'operating_company_state_id' => 'required',
                'operating_company_city_id' => 'required',                
            ];

            
            $validate = Validator::make($request->all(), $rules);
            if ($validate->fails()) {
                return $this->errorResponse($validate->errors(), 400);
            }
            
            if($request->is_govt == 1)
            {
                $request->company_type_id = NULL;
            }
            
            DB::beginTransaction();
            
            UserProfile::where('user_id', $login_user_id)
            ->update([   
                'organization_name' => $request->company_name,    
                'company_type_id' => $request->company_type_id, 
                'is_govt' => $request->is_govt,
                'updated_by' => $login_user_id,
                'updated_at' => Carbon::now(),
            ]);
            
            $userData = UserProfile::where('user_id',$login_user_id)
                    ->first();
            UserProfileHistory::create([
                'gen_user_id' => empty($userData->gen_user_id) ? '' : $userData->gen_user_id,                
                'organization_name' => $request->company_name,
                'company_type_id' => $request->company_type_id, 
                'is_govt' => $request->is_govt,
                'description' => empty($userData->description) ? '' : $userData->description,
                'logo_path' => empty($userData->logo_path) ? '' : $userData->logo_path,
                'website' => empty($userData->website) ? '' : $userData->website,                
                'contact_email' => empty($userData->contact_email) ? '' : $userData->contact_email,            
                'status' => empty($userData->status) ? 0 : $userData->status,
                'crud_type' => 'add',
                
                'user_id' => $login_user_id,
                'contact_persone_name' => $userData->contact_persone_name,
                'contact_mobile_no' => $userData->contact_mobile_no,
                'is_mobile_verified' =>  $userData->is_mobile_verified,
                'mobile_otp' => $userData->mobile_otp,
                'created_at' => Carbon::now(),
                'created_by' => $login_user_id,
                'updated_by' => $login_user_id,
                'updated_at' => Carbon::now(),
            ]);
            
            
            /**************************************
                for company address add or update
            ***************************************/
            
            ProfileAddress::updateOrCreate(
                [   
                    'user_id'=>$login_user_id, 
                    'type' => 1, // 1 - Registred address
                ],
                [
                    'address' => $request->registerd_company_address, 
                    'pincode' => $request->registerd_company_pincode, 
                    'state_id' => $request->registerd_company_state_id, 
                    'city_id' => $request->registerd_company_city_id,  
                    'latitude'=>'', 
                    'logitude'=>'',
                    'created_at' => Carbon::now(),
                    'created_by' => $login_user_id,
                    'updated_by' => $login_user_id,
                    'updated_at' => Carbon::now(),
                ]
            );
            
            ProfileAddress::updateOrCreate(
                [   
                    'user_id'=>$login_user_id, 
                    'type' => 2, // 2 - Operating Address
                ],
                [
                    'address' => $request->operating_company_address, 
                    'pincode' => $request->operating_company_pincode, 
                    'state_id' => $request->operating_company_state_id, 
                    'city_id' => $request->operating_company_city_id, 
                    'latitude'=>'', 
                    'logitude'=>'',
                    'created_at' => Carbon::now(),
                    'created_by' => $login_user_id,
                    'updated_by' => $login_user_id,
                    'updated_at' => Carbon::now(),
                ]
            );


            DB::commit();
            $res = ["success"=>trans('messages.success_added')];
            return $this->successResponse($res);
            
        } catch (\Exception $e) {
            return $this->errorResponse(trans('messages.error_internal'), 400);            
        }
    }

    public function uploadDoc(Request $request) {
        $profileAddress = ProfileAddress::where('user_id',$request->login_user_id)->get();
        if(!$profileAddress->count()){
            if($request->login_user_type_id==2){
                return $this->errorResponse('Please complete organization details section first.', 422);
            }else{
                return $this->errorResponse('Please complete publication details section first.', 422);
            }    
        }

        $userProfile = UserProfile::where('user_id',$request->login_user_id)->first();
        if(empty($userProfile)){
            return $this->errorResponse('Please complete contact info section first.', 422);
        }
        $is_govt = $userProfile->is_govt;
        if($is_govt==1){
            $rules = [
                'verification_document' => 'required|max:3',
                'verification_document.*' => 'file|mimes:pdf,jpg,png,jpeg|max:2048',
                //'verification_doc_number' => 'required',
                'verification_doc_id' => 'required|in:10',
                'address_document' => 'required|max:3',
                'address_document.*' => 'file|mimes:pdf,jpg,png,jpeg',
                'address_doc_id' => 'required',
            ];
        }else{
            $rules = [
                'verification_document' => 'required|max:3',
                'verification_document.*' => 'file|mimes:pdf,jpg,png,jpeg',
                'verification_doc_number' => 'required_unless:verification_doc_id,4',
                'verification_doc_id' => 'required',
                'address_document' => 'required|max:3',
                'address_document.*' => 'mimes:pdf,jpg,png,jpeg',
                'address_doc_id' => 'required',
            ];
        }
        

        $messages = [
            'address_document.max' => 'file can not be more than 3'
        ];

        $validate = Validator::make($request->all(), $rules, $messages);
       
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
        //dd($request->all());
        $login_user_id = $request->login_user_id;
        ProfileDocument::where('user_id', $login_user_id)->delete();
        foreach($request->file('verification_document') as $file) {
            $path = $this->storeFile($file, 'uploads/document');
            ProfileDocument::create([
                'user_id' => $login_user_id,
                'doc_id' => $request->verification_doc_id,
                'doc_path' => $path,
                'created_by' => $login_user_id,
                'doc_number' => $request->verification_doc_number,
                'doc_name' => $file->getClientOriginalName(),
                'doc_size' => $file->getSize()
            ]);
        }
        foreach($request->file('address_document') as $file) {
            $path = $this->storeFile($file,  'uploads/document');
            ProfileDocument::create([
                'user_id' => $login_user_id,
                'doc_id' => $request->address_doc_id,
                'doc_path' => $path,
                'created_by' => $login_user_id,
                'doc_number' => NULL,
                'doc_name' => $file->getClientOriginalName(),
                'doc_size' => $file->getSize()
            ]);
        }
        $userData = User::find($login_user_id);
        $userData->document_status = 1; // document uploaded and pending
       $userData->document_reject_reason = NULL;
        $userData->save();
        $use = User::where('id',$login_user_id)->first();
        try {
            Mail::to($use->email)->send(new SubmitDocument($use));
        } catch(\Exception $e) {

        }
        return $this->successResponse(['success' => 'upload document successfully']);


    }

    public function addPublicationDetail(Request $request) {
        $rules = [
            'publication_name' => 'required',
            'category' => 'required|array',
            'category.*' => 'required',
            'registered_address' => 'required',
            'registered_pincode' => 'required',
            'registered_state' => 'required',
            'registered_city' => 'required',
            'operating_address' => 'required',
            'operating_pincode' => 'required',
            'operating_state' => 'required',
            'operating_city' => 'required',
        ];
        
        $validate = Validator::make($request->all(), $rules);
       
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
       
        $login_user_id = $request->login_user_id;
        $userProfile = UserProfile::where('user_id', $login_user_id)->first();
        $userProfile->organization_name = $request->publication_name;
        $userProfile->created_by = $login_user_id;
        $userProfile->save();
        
        PublicationCategory::where('publication_id', $login_user_id)->delete();
        foreach($request->category as $cat) {
            PublicationCategory::create([
                'publication_id' => $login_user_id,
                'category_id' => $cat,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        $checkRegisteredAddress = ProfileAddress::where('user_id', $login_user_id)->where('type', 1)->first();
        if($checkRegisteredAddress) {
            ProfileAddress::where('user_id', $login_user_id)->where('type', 1)->update([
                'address' => $request->registered_address,
                'pincode' => $request->registered_pincode,
                'state_id' => $request->registered_state,
                'city_id' => $request->registered_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            ProfileAddress::create([
                'user_id' => $login_user_id,
                'type' => 1,
                'address' => $request->registered_address,
                'pincode' => $request->registered_pincode,
                'state_id' => $request->registered_state,
                'city_id' => $request->registered_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        $checkOperatingAddress = ProfileAddress::where('user_id', $login_user_id)->where('type', 2)->first();
        if($checkOperatingAddress) {
            ProfileAddress::where('user_id', $login_user_id)->where('type', 2)->update([
                'address' => $request->operating_address,
                'pincode' => $request->operating_pincode,
                'state_id' => $request->operating_state,
                'city_id' => $request->operating_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            ProfileAddress::create([
                'user_id' => $login_user_id,
                'type' => 2,
                'address' => $request->operating_address,
                'pincode' => $request->operating_pincode,
                'state_id' => $request->operating_state,
                'city_id' => $request->operating_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return $this->successResponse(['success' => 'detail added successfully']);
    }

    public function addGovermentDetail(Request $request) {
        $rules = [
            'organization_name' => 'required',
           // 'organization_type' => 'required',
            'registered_address' => 'required',
            'registered_pincode' => 'required',
            'registered_state' => 'required',
            'registered_city' => 'required',
            'operating_address' => 'required',
            'operating_pincode' => 'required',
            'operating_state' => 'required',
            'operating_city' => 'required',
        ];
        
        $validate = Validator::make($request->all(), $rules);
       
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
       
        $login_user_id = $request->login_user_id;
        $userProfile = UserProfile::where('user_id', $login_user_id)->first();
        $userProfile->organization_name = $request->organization_name;
        $userProfile->created_by = $login_user_id;
        $userProfile->company_type_id = NULL;
        $userProfile->is_govt = 1;
        $userProfile->save();
        
        $checkRegisteredAddress = ProfileAddress::where('user_id', $login_user_id)->where('type', 1)->first();
        if($checkRegisteredAddress) {
            ProfileAddress::where('user_id', $login_user_id)->where('type', 1)->update([
                'address' => $request->registered_address,
                'pincode' => $request->registered_pincode,
                'state_id' => $request->registered_state,
                'city_id' => $request->registered_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            ProfileAddress::create([
                'user_id' => $login_user_id,
                'type' => 1,
                'address' => $request->registered_address,
                'pincode' => $request->registered_pincode,
                'state_id' => $request->registered_state,
                'city_id' => $request->registered_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        $checkOperatingAddress = ProfileAddress::where('user_id', $login_user_id)->where('type', 2)->first();
        if($checkOperatingAddress) {
            ProfileAddress::where('user_id', $login_user_id)->where('type', 2)->update([
                'address' => $request->operating_address,
                'pincode' => $request->operating_pincode,
                'state_id' => $request->operating_state,
                'city_id' => $request->operating_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            ProfileAddress::create([
                'user_id' => $login_user_id,
                'type' => 2,
                'address' => $request->operating_address,
                'pincode' => $request->operating_pincode,
                'state_id' => $request->operating_state,
                'city_id' => $request->operating_city,
                'created_by' => $login_user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return $this->successResponse(['success' => 'detail added successfully']);
    }

    public function getCategoryList() {
        $categoryList = CategoryMaster::select('id', 'name')->where('status', '1')->get();
        return $this->successResponse($categoryList);
    }

    public function getOrganizationDetail(Request $request) {
        $login_user_type_id = $request->login_user_type_id;
        $login_user_id = $request->login_user_id; 
        $userProfile = UserProfile::where('user_id', $login_user_id)->first();
        if($login_user_type_id==3){
            $categoryList = PublicationCategory::where('publication_id', $login_user_id)->get();
        }    
        $registerAddress = ProfileAddress::where('user_id', $login_user_id)->where('type', 1)->first();
        $operatingAddress = ProfileAddress::where('user_id', $login_user_id)->where('type', 2)->first();

        $data = ['userProfile' => $userProfile, 'registerAddress' => $registerAddress, 'operatingAddress' => $operatingAddress];

        if($login_user_type_id==3){
            $data['publicationCategory'] = $categoryList; 
        }
        return $this->successResponse($data);
    }

    public function updateContactPersonName(Request $request){
        $rules = [
            'contact_person_name' => 'required|string|max:60',
        ];
        
        $validate = Validator::make($request->all(), $rules);
       
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
       
        $login_user_id = $request->login_user_id;
        
        $userProfile = UserProfile::where('user_id',$login_user_id)->first();
        if(empty($userProfile)){
            return $this->errorResponse(trans('messages.not_found'),404);
        } 
        $userProfile->contact_persone_name = $request->contact_person_name;
        $userProfile->updated_at = Carbon::now();
        $userProfile->updated_by = $login_user_id;
        $userProfile->update();
        UserProfileHistory::create([        
                'gen_user_id' =>  $userProfile->gen_user_id,
                'organization_name' => $userProfile->organization_name,
                'description' => $userProfile->description,
                'logo_path' => $userProfile->logo_path,
                'company_type_id' => $userProfile->company_type_id,
                'website' => $userProfile->website,
                'is_govt' => $userProfile->is_govt,               
                'contact_email' => $userProfile->contact_email,            
                'status' => $userProfile->status,
                'crud_type' => 'update',
                
                'user_id' => $userProfile->user_id,
                'contact_persone_name' => $userProfile->contact_persone_name,
                'contact_mobile_no' => $userProfile->contact_mobile_no,
                'is_mobile_verified' =>  $userProfile->is_mobile_verified,
                'mobile_otp' => $userProfile->mobile_otp,
                'created_at' => Carbon::now(),
                'created_by' => $login_user_id,
                'updated_by' => $login_user_id,
                'updated_at' => Carbon::now(),
            ]);
        return $this->successResponse(["success"=>trans('messages.success_edited')]);

    }
    
    
    public function deleteDoc(Request $request){
        $rules = [
            'id' => 'required|numeric',
        ];        
        $validate = Validator::make($request->all(), $rules);       
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 400);
        }
        $login_user_id = $request->login_user_id;
        ProfileDocument::where('user_id', $login_user_id)->where('id', $request->id)->delete();
        $res = ["success"=>trans('messages.success_deleted')];
        return $this->successResponse($res);
    }

    public function updateDoc(Request $request) {
        $profileAddress = ProfileAddress::where('user_id',$request->login_user_id)->get();
        if(!$profileAddress->count()){
            if($request->login_user_type_id==2){
                return $this->errorResponse('Please complete organization details section first.', 422);
            }else{
                return $this->errorResponse('Please complete publication details section first.', 422);
            }    
        }

        $userProfile = UserProfile::where('user_id',$request->login_user_id)->first();
        if(empty($userProfile)){
            return $this->errorResponse('Please complete contact info section first.', 422);
        }
        $is_govt = $userProfile->is_govt;
        if($is_govt==1){
            $rules = [
                'verification_document.*' => 'file|mimes:pdf,jpg,png,jpeg|max:2048',
                //'verification_doc_number' => 'required',
                'verification_doc_id' => 'sometimes|in:10',
                'address_document.*' => 'file|mimes:pdf,jpg,png,jpeg',
            ];
        }else{
            $rules = [
                'verification_document.*' => 'file|mimes:pdf,jpg,png,jpeg',
                'address_document.*' => 'mimes:pdf,jpg,png,jpeg',
            ];
        }
        
        $validate = Validator::make($request->all(), $rules);
       
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
        //dd($request->all());
        $login_user_id = $request->login_user_id;
        if($request->file('verification_document')) {
            foreach($request->file('verification_document') as $file) {

                $path = $this->storeFile($file, 'uploads/document');
                ProfileDocument::create([
                    'user_id' => $login_user_id,
                    'doc_id' => $request->verification_doc_id,
                    'doc_path' => $path,
                    'created_by' => $login_user_id,
                    'doc_number' => $request->verification_doc_number,
                    'doc_name' => $file->getClientOriginalName(),
                    'doc_size' => $file->getSize()
                ]);
            }
        }
        if($request->file('address_document')) {
            foreach($request->file('address_document') as $file) {
                $path = $this->storeFile($file,  'uploads/document');
                ProfileDocument::create([
                    'user_id' => $login_user_id,
                    'doc_id' => $request->address_doc_id,
                    'doc_path' => $path,
                    'created_by' => $login_user_id,
                    'doc_number' => NULL,
                    'doc_name' => $file->getClientOriginalName(),
                    'doc_size' => $file->getSize()
                ]);
            }
        }
        $userData = User::find($login_user_id);
        $userData->document_status = 1; // document uploaded and pending
        $userData->document_reject_reason = NULL;
        $userData->save();
        $use = User::where('id',$login_user_id)->first();
        try {
            Mail::to($use->email)->send(new SubmitDocument($use));
        } catch(\Exception $e) {

        }
        return $this->successResponse(['success' => 'update document successfully']);


    }

}
