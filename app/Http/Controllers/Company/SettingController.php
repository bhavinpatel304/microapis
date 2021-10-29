<?php

namespace App\Http\Controllers\Company;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Traits\MediaFiles;
use App\Models\UserProfile;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Models\ProfileAddress;
use App\Models\ProfileDocument;
use App\Services\AuthApiService;
use App\Models\UserProfileHistory;
use App\Http\Controllers\Controller;

class SettingController extends Controller
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

    public function updateLogo(Request $request) {
        $rules = [
            'logo_image' => 'required|mimes:jpg,png,jpeg',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 400);
        }

        $login_user_id = $request->login_user_id;
        $logo_path = $this->storeFile($request->file('logo_image'),  'uploads/logo');
        
        UserProfile::where('user_id', $login_user_id)
        ->update([   
            'logo_path' => $logo_path,
            'updated_by' => $login_user_id,
            'updated_at' => Carbon::now(),
        ]);
        
        $userData = UserProfile::where('user_id',$login_user_id)->first();
        UserProfileHistory::create([
            'gen_user_id' => empty($userData->gen_user_id) ? '' : $userData->gen_user_id,
            'organization_name' => empty($userData->organization_name) ? '' : $userData->organization_name,
            'company_type_id' => $userData->company_type_id,
            'is_govt' => $userData->is_govt,
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
        return $this->successResponse(["success"=>trans('messages.success_edited'),'logo_path'=>$userData->logo_path]);
    }

    public function editOrganisationDetails(Request $request) {
        try 
        {
            $login_user_id = $request->login_user_id;
            $rules = [
                'registerd_company_address' => 'required|max:128',
                'registerd_company_pincode' => 'required',
                'registerd_company_state_id' => 'required',
                'registerd_company_city_id' => 'required',
                'operating_company_address' => 'required|max:128',
                'operating_company_pincode' => 'required',
                'operating_company_state_id' => 'required',
                'operating_company_city_id' => 'required',

                'address_document' => 'required|max:3',
                'address_document.*' => 'mimes:pdf,jpg,png,jpeg',
                'address_doc_id' => 'required',
            ];

            $messages = [
                'address_document.max' => 'file can not be more than 3'
            ];

            $validate = Validator::make($request->all(), $rules, $messages);
            if ($validate->fails()) {
                return $this->errorResponse($validate->errors(), 400);
            }

            DB::beginTransaction();

            /*****************************
              address Document update
            *****************************/
            ProfileDocument::where('user_id', $login_user_id)->delete();
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

            /*****************************
              address update
            *****************************/
            
            ProfileAddress::where('user_id',$login_user_id)->where('type', 1)->update(
                [
                    'address' => $request->registerd_company_address,
                    'pincode' => $request->registerd_company_pincode,
                    'state_id' => $request->registerd_company_state_id,
                    'city_id' => $request->registerd_company_city_id,
                    'updated_by' => $login_user_id,
                    'updated_at' => Carbon::now(),
                ]
            );
            
            ProfileAddress::where('user_id',$login_user_id)->where('type', 2)->update(
                [
                    'address' => $request->operating_company_address,
                    'pincode' => $request->operating_company_pincode,
                    'state_id' => $request->operating_company_state_id,
                    'city_id' => $request->operating_company_city_id,
                    'updated_by' => $login_user_id,
                    'updated_at' => Carbon::now(),
                ]
            );

            DB::commit();
            $res = ["success"=>trans('messages.success_edited')];
            return $this->successResponse($res);
            
        } catch (\Exception $e) {
            return $this->errorResponse(trans('messages.error_internal'), 400);            
        }
    }
    
}    
