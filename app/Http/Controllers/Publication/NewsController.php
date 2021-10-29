<?php

namespace App\Http\Controllers\Publication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\ApiResponser;
use App\Traits\MediaFiles;
use App\Services\NewsService;
use Carbon\Carbon;
use Validator;
use App\Models\ProfileAddress;
use App\Models\User;
use App\Models\CategoryMaster;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Mail;
use DB;
use App\Mail\Publication\ChangeNewsStatus;


class NewsController extends Controller
{
    use ApiResponser,MediaFiles;
    public $newsService;
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }
    
     public function publication_listNews(Request $request){
        $input =  $request->all();
        $input['company_search_ids'] = [];        
        if($request->search){            
            $companySearchIds = User::select('id')
                                    ->whereHas('profile',function($q) use($request){
                                        $q->where('organization_name','LIKE',"%{$request->search}%");
                                    })
                                    ->where('user_type_id',2)
                                    ->where('status',1)
                                    ->where('payment_status',1)
                                    ->get()
                                    ->pluck('id')->toArray();
                                   
            $input['company_search_ids'] = $companySearchIds;
        }
        
        $newsList =  $this->successResponse($this->newsService->publication_viewAllRequests($input));

        $newsList  = json_decode($newsList->original);
        
        $allCategory = CategoryMaster::select('id','name')->get();
        foreach($allCategory as &$allCat){
            $allCat->total=0;
            foreach($newsList->categoryWiseCount as $catWiseCount){
                if($catWiseCount->category_id==$allCat->id){
                    $allCat->total = $catWiseCount->total;
                }
            }
        }
       $newsList->categoryWiseCount = $allCategory;

       $allCategory = $allCategory->pluck('name', 'id');
        $allCompanies = UserProfile::join('user_details', 'user_profile.user_id', 'user_details.id')
            ->where('user_details.user_type_id', 2)
            ->pluck('user_profile.organization_name', 'user_details.id');
       
        foreach($newsList->data as &$news) {
            foreach($news->categories as $category){
                $category->name = $allCategory[$category->category_id] ?? '';
            }
//            foreach($news->publications as $publication){
                $news->organization_name = $allCompanies[$news->created_by] ?? '';
//            }
        }
        
        return $this->successResponse(json_encode($newsList));
    }
    
    public function publication_viewNews(Request $request, $news_id){
        
        $input = [
            'news_id' => $news_id,
            'login_user_id' => $request->login_user_id
        ];
        $input['company_search_ids'] = [];
        
        $response = $this->newsService->publication_viewNews($input);
        
        if(empty($response)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
       
        $newsList =  $this->successResponse($response);
       
        $newsList  = json_decode($newsList->original);
        $allCategory = CategoryMaster::select('id','name')->get();
        $allCategory = $allCategory->pluck('name', 'id');
        $allCompanies = UserProfile::join('user_details', 'user_profile.user_id', 'user_details.id')
            ->where('user_details.user_type_id', 2) // 2 for  company user type
            ->pluck('user_profile.organization_name', 'user_details.id');
        
        foreach($newsList->categories as $category){
            $category->name = $allCategory[$category->category_id] ?? '';
        }
        
        $newsList->organization_name = $allCompanies[$newsList->created_by] ?? '';
        return $this->successResponse(json_encode($newsList));
    }
    
    public function publication_acceptStatus(Request $request, $news_id){
        $data = [
            'news_id' => $news_id,
            'login_user_id' => $request->login_user_id
        ];
        
        $response = $this->newsService->publication_acceptStatus($data);
        
        if(empty($response)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
        
        $responseArray = json_decode($response);
        $user = User::find($responseArray->created_by);
        $strToEmail = $user->email;
        $strToName = $user->fname." ".$user->lname;   
        $mailData['user'] = $user;
        $mailData['new_status'] = "Accepted";
        Mail::to($strToEmail)->send(new ChangeNewsStatus($mailData));
        
        $res = ["success"=>trans('messages.success_accepted')];
        return $this->successResponse($res);
    }
    
    public function publication_readyForPublishStatus(Request $request, $news_id){
        $data = [
            'news_id' => $news_id,            
            'login_user_id' => $request->login_user_id
        ];
        
        if(!empty($request->publishing_date)){
            $data['publishing_date'] = $request->publishing_date;
        }
         
        $response = $this->newsService->publication_readyForPublishStatus($data);
        
        if(empty($response)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
        
        $responseArray = json_decode($response);
        $user = User::find($responseArray->created_by);
        $strToEmail = $user->email;
        $strToName = $user->fname." ".$user->lname;   
        $mailData['user'] = $user;
        $mailData['new_status'] = "Ready For Publish";
        if(!empty($request->publishing_date)){
             $mailData['publishing_date'] = Carbon::parse($request->publishing_date)->format("Y-m-d");
        }
        Mail::to($strToEmail)->send(new ChangeNewsStatus($mailData));
        
        $res = ["success"=>trans('messages.success_ready_for_publish')];
        return $this->successResponse($res);
    }
    
    public function publication_rejectStatus(Request $request, $news_id){
        $data = [
            'news_id' => $news_id,
            'reject_reason' => $request->reject_reason,
            'login_user_id' => $request->login_user_id
        ];
        
        $response = $this->newsService->publication_rejectStatus($data);
        
        if(empty($response)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
        
        $responseArray = json_decode($response);
        $user = User::find($responseArray->created_by);
        $strToEmail = $user->email;
        $strToName = $user->fname." ".$user->lname;   
        $mailData['user'] = $user;
        $mailData['new_status'] = "Rejected";
        $mailData['reject_reason'] = $data['reject_reason'];

        Mail::to($strToEmail)->send(new ChangeNewsStatus($mailData));
        
        $res = ["success"=>trans('messages.success_rejected')];
        return $this->successResponse($res);
    }
    
    public function publication_revokeStatus(Request $request, $news_id){
      
            $data = [
                'news_id' => $news_id,
                'login_user_id' => $request->login_user_id,
            ];
            
            $response = $this->newsService->publication_revokeStatus($data);
        
            if(empty($response)){
                return $this->errorResponse(trans('messages.not_found'),404);
            }
            $responseArray = json_decode($response);
            $user = User::find($responseArray->created_by);
            $strToEmail = $user->email;
            $strToName = $user->fname." ".$user->lname;   
            $mailData['user'] = $user;
            $mailData['new_status'] = "Accepted";            
            Mail::to($strToEmail)->send(new ChangeNewsStatus($mailData));
            $res = ["success"=>trans('messages.success_revoked')];
            return $this->successResponse($res);
        
    }
    
    public function publication_updatePublishingDate(Request $request, $news_id){

        
            $data = [
                'news_id' => $news_id,
                'login_user_id' => $request->login_user_id,                
                'publishing_date' => $request->publishing_date,
            ];
          
            $response = $this->newsService->publication_updatePublishingDate($data);
        
            if(empty($response)){
                return $this->errorResponse(trans('messages.not_found'),404);
            }
            
            $responseArray = json_decode($response); 
            $user = User::find($responseArray->created_by);
            $strToEmail = $user->email;
            $strToName = $user->fname." ".$user->lname;   
            $mailData['user'] = $user;
            $mailData['publishing_date'] = $responseArray->publishing_date;
            
            Mail::to($strToEmail)->send(new ChangeNewsStatus($mailData));
            $res = ["success"=>trans('messages.success_edited')];
            return $this->successResponse($res);
        
    }
    
}    
