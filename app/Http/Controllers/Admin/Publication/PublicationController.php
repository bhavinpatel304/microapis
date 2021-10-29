<?php

namespace App\Http\Controllers\Admin\Publication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\CompanyType;
use App\Traits\ApiResponser;
use GuzzleHttp\Client;
use Validator;
use DB;
use App\Models\ProfileDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyDocument;
use App\Mail\RejectDocument;
use App\Services\NewsService;
class PublicationController extends Controller
{
    use ApiResponser;

    
    public $newsService;
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }
    
    public function getUsers(Request $request)
    {
        $query = User::select('user_details.id', 'user_profile.organization_name', 'user_profile.contact_email', 'user_profile.contact_mobile_no', 'user_details.status', 'user_details.entity_id', DB::raw('"2020-01-01" AS membership_start_date') )
            ->join('user_profile', 'user_details.id', 'user_profile.user_id')
            ->where('user_details.user_type_id', '=', $request->user_type_id)
            ->where('user_details.document_status', 2);
        if($request->search) {
            $query->where(function($q) use ($request) {
                $q->where("user_profile.organization_name", 'LIKE', "%{$request->search}%")
                ->orWhere('user_profile.contact_email','LIKE', "%{$request->search}%")
                ->orWhere('user_profile.contact_mobile_no','LIKE', "%{$request->search}%");
            });
        }
        if($request->status) {
            $query->where('user_details.status', $request->status);
        }
        $publicationData = $query->paginate(5);
        return $this->successResponse($publicationData);
    }

    public function getPublicationDetail($id) {
        $publicationDetail = User::select('id', 'email', DB::raw('CONCAT(fname," ",lname) AS fullname'), 'contact_no', 'status', 'entity_id')
            ->with(['profile' => function($q) {
                $q->select('user_id', 'organization_name', 'logo_path');
            }, 'profile_document' => function($q) {
                $q->select('user_id', 'doc_id', 'doc_name', 'doc_number', 'doc_path', 'doc_size');
            }, 'profile_address' => function($q) {
                $q->select('user_id', 'type', 'address', 'pincode', 'state_id', 'city_id');
            }])->where('id', $id)->where('user_type_id', 3)->first();
        if(!$publicationDetail) {
            return $this->errorResponse(trans('messages.publication_not_found'),500);;
        }
        $data['publication_id'] = $id; 
        $newsCount = $this->newsService->getNewsCount($data);
        $publicationDetail->newsCountDetail = json_decode($newsCount);
        return $this->successResponse($publicationDetail);
    }
    
  
}    
