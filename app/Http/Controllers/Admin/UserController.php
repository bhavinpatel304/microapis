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
class UserController extends Controller
{
    use ApiResponser;

    
    public function __construct()
    {
    }
    
    public function viewAllRequests(Request $request)
    {
  
        $query = User::with(['type'=>function($q){
            $q->select('id','name');
            }])
            ->select('id',DB::raw('CONCAT(fname," ",lname) AS fullname'),'document_status','payment_status','status','email_verified_at','user_type_id','document_reject_reason')
            ->where('user_type_id', '!=', 1);
        
        if($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereRaw("CONCAT(fname,' ',lname) like ?",["%{$request->search}%"])//'fname','LIKE', "%{$request->search}%")
                ->orWhere('contact_no','LIKE', "%{$request->search}%")
                ->orWhere('email','LIKE', "%{$request->search}%");
            });
        }
        if($request->user_type_id) {
            $query->where('user_type_id', $request->user_type_id);
        }
        if($request->document_status) {
            $query->where('document_status', $request->document_status);
        }
        if($request->payment_status==="0" || $request->payment_status==1) {
            $query->where('payment_status', $request->payment_status);
        }
        if($request->order_by && $request->order) {
            $query->orderBy($request->order_by, $request->order);
        }else{
            $query->orderBy('id','DESC');
        }
        
        $userData = $query->paginate(10);
        return $this->successResponse($userData);
    }
    
    /*
    * if user is active then do inactive
    * else do active
    */
    public function changeUserStatus(Request $request)
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
                    ->where('id',$request->user_id)
                    ->where('id','!=',$request->login_user_id)
                    ->where('user_type_id','!=',1)
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


    public function listDocument($userId,Request $request){
        $user = User::where('id',$userId)->first();
        if(empty($user)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
        if($user->document_status==0){
            return $this->errorResponse(trans('messages.verification_document_not_uploaded'),400);
        }
        
        $userDocuments = ProfileDocument::with(['documentType'=>function($q){
            $q->select('id','name','type');
        }])->where('user_id',$userId)->get();

        return $this->successResponse($userDocuments);

    }

    public function verifyDocument($userId,Request $request){
        $user = User::where('id',$userId)->first();
        if(empty($user)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }

        if($user->document_status==0){
            return $this->errorResponse(trans('messages.verification_document_not_uploaded'),400);   
        }elseif($user->document_status==2){ 
            return $this->errorResponse(trans('messages.verification_document_already_verified'),400);
        }elseif($user->payment_status==1){
            return $this->errorResponse(trans('messages.cant_verify_reject_after_payment_done'),400);
        }
        $entityId = $this->getEntityId($userId, $user);
        $user->document_status = 2; //verify 
        $user->payment_status = 1;
        $user->document_reject_reason = NULL;
        $user->updated_at = Carbon::now();
        $user->entity_id = $entityId;
        $user->update();
        $use = User::where('id',$userId)->first();
        try {
            Mail::to($use->email)->send(new VerifyDocument($use));
        } catch(\Exception $e) {

        }
        //notification email
        return $this->successResponse(['success'=>trans('messages.verification_document_success_verified')]);
    }

    public function rejectDocument($userId,Request $request){
        $rules = [
            'reject_reason' => 'required|string',
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()){
            return $this->errorResponse($validate->errors(), 400);
        }

        $user = User::where('id',$userId)->first();
        if(empty($user)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }

        if($user->document_status==0){
            return $this->errorResponse(trans('messages.verification_document_not_uploaded'),400);   
        }elseif($user->payment_status==1){
            return $this->errorResponse(trans('messages.cant_verify_reject_after_payment_done'),400);
        }

        $user->document_status = 3; //reject
        $user->document_reject_reason = $request->reject_reason;
        $user->updated_at = Carbon::now();
        $user->update();
        $use = User::where('id',$userId)->first();
        try {
            Mail::to($use->email)->send(new RejectDocument($use, $request->reject_reason));
        } catch(\Exception $e) {

        }
        //notification email with reject message also
        return $this->successResponse(['success'=>trans('messages.verification_document_success_rejected')]);
    }

    public function getEntityId($userId, $user) {
        $entityId = '';
        if($user->user_type_id == 3) {
            $entityId = 'P';
            $userCount = User::where('user_type_id', 3)->where('user_details.id', '!=', $userId)->count()+1001;
            $entityId = $entityId . $userCount;
        } elseif($user->user_type_id == 2) {
            $userProfile = UserProfile::where('user_id', $userId)->first();
            if($userProfile->is_govt == 1) {
                $userCount = User::
                    join('user_profile', 'user_details.id', 'user_profile.user_id')
                    ->where('user_details.user_type_id', 2)
                    ->where('user_profile.is_govt', 1)
                    ->where('user_details.id', '!=', $userId)
                    ->count()+1001;
                $entityId = 'G' . $userCount;
            } else {
                $userCount = User::
                    join('user_profile', 'user_details.id', 'user_profile.user_id')
                    ->where('user_details.user_type_id', 2)
                    ->where('user_profile.is_govt', '!=', 1)
                    ->where('user_details.id', '!=', $userId)
                    ->count()+1001;
                $entityId = 'C'. $userCount;
            }
        }
        return $entityId;
    }
}