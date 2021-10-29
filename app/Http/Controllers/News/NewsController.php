<?php

namespace App\Http\Controllers\News;

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

class NewsController extends Controller
{
    use ApiResponser,MediaFiles;
    public $newsService;
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }
    public function getPublicationListNearBy(Request $request) {
        $login_user_id = $request->login_user_id;
        $address = ProfileAddress::where('type', 2)->where('user_id', $login_user_id)->first();
        $nearByPublisher = User::select('user_details.id', 'user_profile.organization_name')
            ->join('user_profile', 'user_details.id', 'user_profile.user_id')
            ->join('profile_address', 'user_details.id', 'profile_address.user_id')
            ->where('user_details.user_type_id', 3)
            ->where('profile_address.type', 2)
            ->where('profile_address.city_id', $address->city_id)
            ->get();
        $nearByArray = [];
        foreach($nearByPublisher as &$data) {
            $data->publication_id = $data->id;
            $nearByArray[] = $data->id;
        }

        $allPublisher = User::select('user_details.id', 'user_profile.organization_name')
            ->join('user_profile', 'user_details.id', 'user_profile.user_id')
            ->where('user_details.user_type_id', 3)
            ->whereNotIn('user_details.id', $nearByArray)
            ->get();
        foreach($allPublisher as &$allPub) {
            $allPub->publication_id = $allPub->id;
        }
        $publisherData = ['nearByPublisher'=> $nearByPublisher, 'allPublisher' => $allPublisher];
        return $this->successResponse($publisherData);
    }

    public function storeNews(Request $request){
        return $this->successResponse($this->newsService->storeNews($request));
    }
    
    public function viewAllRequests(Request $request){
        return $this->successResponse($this->newsService->viewAllRequests($request));
    }
    
}    
