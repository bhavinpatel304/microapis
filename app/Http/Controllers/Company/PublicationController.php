<?php

namespace App\Http\Controllers\Company;

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
use App\Services\ChatService;
use App\Services\NewsService;
use App\Models\CategoryMaster;

class PublicationController extends Controller
{
    use ApiResponser,MediaFiles;

    /**
    * The service to consume the Auth micro-service
    * 
    */


    public $chatService, $newsService;
    public function __construct(ChatService $chatService, NewsService $newsService )
    {
        $this->chatService = $chatService;
        $this->newsService = $newsService;
    }

    public function getPublications(Request $request) {
        $data['login_user_id'] = $request->login_user_id; 
        $publicationIds = $this->newsService->getActivePublicationId($data);
        $query = User::select('user_details.id', 'user_profile.organization_name', 'user_profile.logo_path')
            ->join('user_profile', 'user_details.id', 'user_profile.user_id')
            ->whereIn('user_details.id', json_decode($publicationIds))
            ->where('user_type_id', 3);
        if($request->search) {
            $query = $query->where('user_profile.organization_name', 'LIKE', "%{$request->search}%");
        }
        $publications =  $query->get();
        return $this->successResponse($publications);
    }

    public function recentPublicationList(Request $request) {
        $data['publication_id'] = $request->login_user_id;
        $getRecentUser = $this->chatService->getRecentPublication($data);
        $userArray = [];
        $getRecentUserArray = json_decode($getRecentUser);
        foreach($getRecentUserArray as $data) {
            $userArray[] = $data->user_id;
        }
        $query = UserProfile::select('user_id as id', 'organization_name', 'logo_path')->whereIn('user_id', $userArray);
        if($request->search) {
            $query = $query->where('organization_name', 'LIKE', "%{$request->search}%");
        }
        $getUsers =  $query->get();
        return $this->successResponse($getUsers);  
    }

    public function getNewsByPublication($publication_id, Request $request) {
        $data['publication_id'] = $publication_id;
        $data['search'] = $request->search;
        $data['login_user_id'] = $request->login_user_id;
        $getNews = $this->newsService->getNewsByPublicationId($data);
        
        $allCategory = CategoryMaster::pluck('name', 'id');
        $getNews = json_decode($getNews);
        $chatData['news_id'] = [];
        foreach($getNews as $news){
            $news->publication_id = $publication_id;
            $chatData['news_id'][] = $news->id;
            foreach($news->categories as $category){
                $category->name = $allCategory[$category->category_id] ?? '';
            }
        }
        $chat = $this->chatService->getLastDateOfNews($chatData);
        $getChat = json_decode($chat);
        foreach($getNews as $news){
            foreach($getChat as $ch) {
                if($news->id == $ch->news_id) {
                    $news->last_message_time = $ch->created_at;
                }
            }
        }
        return $this->successResponse($getNews); 
    }
}    
