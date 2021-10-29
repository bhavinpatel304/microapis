<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\CompanyType;
use App\Models\ProfileDocument;
use App\Models\UserMembership;
use App\Traits\ApiResponser;
use GuzzleHttp\Client;
use Validator;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyDocument;
use App\Mail\RejectDocument;
use App\Models\UserProfile;
use App\Services\NewsService;
class EntityController extends Controller
{
    use ApiResponser;
    public $newsService;
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }
    
    
    /*
    ********For User Management => Tab Company*****************
    ***************Paid users *********************************
    */
    public function viewAllRequestsCompany(Request $request)
    { 
        DB::enableQueryLog();
        $query = User::select('id','entity_id') //,'email','contact_no')                
                        
            ->with(
                [   'membership' => function($q) use ($request){ 
                        $q->select('user_id','membership_id','start_date','status');
                        
                        $q->with([ 'membershiptype' => function($q){
                            $q->select('id','gen_membership_id','name');
                        }]);
                    },
                    'profile' => function($q){ 
                        $q->select('organization_name','contact_email','contact_mobile_no','user_id');
                    }
                ]

            )
            ->whereHas(
                'membership' , function($q) use($request) { 
                    $q->select('user_id','membership_id','status');
                    $q->with([ 'membershiptype' => function($q){
                        $q->select('id');
                    }]);
                }
                    
            );
            
            if($request->search) {
                
                $query = $query->whereHas(
                    'profile' , function($q) use($request) {                     
                        $q->where(function($q) use ($request) {
                            $q->where('organization_name','LIKE', "%{$request->search}%")
                            ->orWhere('contact_email','LIKE', "%{$request->search}%")
                            ->orWhere('user_id','LIKE', "%{$request->search}%")
                            ->orWhere('contact_mobile_no','LIKE', "%{$request->search}%");
                        });                    
                    }                    
                );            
                
                $query = $query->orWhere('entity_id','LIKE', "%{$request->search}%");
            }
            
            $query = $query->where('user_type_id', 2)->where('payment_status', 1);
            
            if($request->status) {
                 $query = $query->where('status',$request->status);
            }
           
            $query->orderBy('id', 'ASC');
       
        $userData = $query->paginate(5);
        
//      dd(DB::getQueryLog());
        return $this->successResponse($userData);
    }
    
    /*
    * if user is active then do inactive
    * else do active
    */
    public function changeMemebershipStatus(Request $request)
    {        
        try {
            $rules = [
                'user_id' => 'required',
            ];
            
            $validate = Validator::make($request->all(), $rules);
            if ($validate->fails()) {
                return $this->errorResponse($validate->errors(), 400);
            }
            
            $userData = User::select('id','status')
                    ->where('user_id',$request->user_id)
                    ->first();
            
            if(empty($userData))
            {
                return $this->errorResponse(trans('messages.not_found'),500);
            }
            
            if($userData->status == 1)
            {
                $status = 0;
            }
            else
            {
                $status = 1;
            }
            
            User::where('id', $request->user_id)->update(['status' => $status,'updated_by' => $request->login_user_id]);
           
            $res = ["success"=>trans('messages.success_edited')];
            return $this->successResponse($res);
            
        } catch (\Exception $e) {
           return $this->errorResponse(trans('messages.error_internal'), 400);
        }
    }
    
    public function viewSpecificCompany(Request $request, $id)
    {
        $companyData = User::select('id', 'email', DB::raw('CONCAT(fname," ",lname) AS fullname'), 'contact_no', 'status', 'entity_id')
            ->with(['profile' => function($q) {
                $q->select('user_id', 'organization_name', 'logo_path');
            }, 'profile_document' => function($q) {
                $q->select('user_id', 'doc_id', 'doc_name', 'doc_number', 'doc_path', 'doc_size');
            },
                'membership' => function($q) { 
                    $q->select('user_id','membership_id');
                    $q->with([ 'membershiptype' => function($q){
                        $q->select('id','gen_membership_id','name');
                    }]);
            }, 'profile_address' => function($q) {
                $q->select('user_id', 'type', 'address', 'pincode', 'state_id', 'city_id');
            }])->where('id', $id)->where('user_type_id', 2)->first();
            
        if(!$companyData) {
            return $this->errorResponse(trans('messages.company_not_found'),500);;
        }
        
        $data['id'] = $id;
        $newsCount = $this->newsService->company_getNewsCount($data);
        $companyData->newsCountDetail = json_decode($newsCount);
        return $this->successResponse($companyData);
    }
    
}    
