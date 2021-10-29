<?php

namespace App\Http\Controllers\Publication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\ApiResponser;
use App\Traits\MediaFiles;
use App\Services\ChatService;
use App\Services\NewsService;
use Carbon\Carbon;
use Validator;
use App\Models\User;
use App\Models\CategoryMaster;
use App\Models\UserProfile;

class CompanyController extends Controller
{
    use ApiResponser,MediaFiles;
    public $chatService;
    public $newsService;
    public function __construct(ChatService $chatService,NewsService $newsService)
    {
        $this->chatService = $chatService;
        $this->newsService = $newsService;
    }    
    
    /*
     * Usage => Publication User - view all compnies
     */
    public function getCompany(Request $request) {
        try{
            
            $data = $request->all();
            $data['publication_id'] = $request->login_user_id;

            $newsList = $this->newsService->chat_getNewsByPublicationID($data);
            $newsList = json_decode($newsList);
            
            $companiesID = array_unique(array_column($newsList, 'created_by'));
            
            $companyData = UserProfile::select('user_id as id','organization_name','logo_path')
                ->whereIn('user_id',$companiesID);
            if($request->search) {
                $companyData = $companyData->where('organization_name','LIKE', "%{$request->search}%");
            }
            
            $companyData = $companyData->get();
            
            if($companyData->isEmpty()){
                return $this->errorResponse(trans('messages.company_not_found'),200);
            }
                
            return $this->successResponse($companyData);
        }
        catch(\Exception $e) {
            return $this->errorResponse($e->getMessage(),400);
        }
        
    }
    
    /*
     * Usage => Publication User - view all news assigned by a company
     */
    public function getCompanyNewsByID(Request $request,$id) {  
        $data = $request->all();
        $data['company_id'] = $id;
        
        try{
            $newsList = $this->newsService->chat_getCompanyNewsByID($data);
            $newsList = json_decode($newsList);
            
            if(empty($newsList)){
                return $this->errorResponse(trans('messages.company_not_found'),200);
            }
            
            $newsIDs['news_ids'] = array_column($newsList,"id");
            
            $lastMsgs = $this->chatService->publication_getLastMsgTime($newsIDs);
         
            $lastMsgs = json_decode($lastMsgs);
            $allCategory = CategoryMaster::pluck('name', 'id');
            
            foreach($newsList as $news){
                foreach($lastMsgs as $lastM){
                    if($news->id == $lastM->news_id)
                    $news->last_text_time = $lastM->last_text_time;
                }
                foreach($news->categories as $category){
                    $category->name = $allCategory[$category->category_id] ?? '';
                }
            }
            
            return $this->successResponse($newsList); 
        }
        catch(\Exception $e) {
            return $this->errorResponse($e->getMessage(),400);
        }
    }
    
    /*
     * Usage => Publication User - See recent chat messages from companies
     */
    public function getRecentChat(Request $request) {   
        try{
            $data = $request->all();
            $chatData = $this->chatService->publication_getRecentChat($data);
            $chatData = json_decode($chatData);
            if(empty($chatData)){
                return $this->errorResponse(trans('messages.company_not_found'),200);
            }
 
            $companyIDArray = array_column($chatData,'company_id');
            $companyIDArray = array_unique(array_column($chatData,'company_id'));

            $companyData = UserProfile::select('user_id as id','organization_name','logo_path')
            ->whereIn('user_id', $companyIDArray);
            if($request->search){  
                $companyData = $companyData->where('organization_name','LIKE', "%{$request->search}%");
            }       
            $companyData = $companyData->limit(10)->get();
           
            if($companyData->isEmpty()){
                return $this->errorResponse(trans('messages.company_not_found'),200);
            }

            return $this->successResponse($companyData);
        }
        catch(\Exception $e) {
            return $this->errorResponse($e->getMessage(),400);
        }
    }
}    
