<?php

namespace App\Http\Controllers\Company;

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
use DB;


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
            ->where('payment_status',1)
            ->where('user_details.status',1)
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
            ->where('payment_status',1)
            ->where('user_details.status',1)
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

    public function getDeletedNews(Request $request){
        $deletedNewsJson =  $this->successResponse($this->newsService->getDeletedNewsList($request));
        $deletedNews  = json_decode($deletedNewsJson->original);
        $allCategory = CategoryMaster::pluck('name', 'id');
        $allPublication = UserProfile::join('user_details', 'user_profile.user_id', 'user_details.id')
            ->where('user_details.user_type_id', 3)->pluck('user_profile.organization_name', 'user_details.id');
        foreach($deletedNews as $news) {
            foreach($news->categories as $category){
                $category->category_name = $allCategory[$category->category_id];
            }
            foreach($news->publications as $publication){
                $publication->organization_name = $allPublication[$publication->publication_id];
            }
        }
        return $this->successResponse($deletedNews);
    }

    public function listNews(Request $request){
        $input =  $request->all();
        $input['publication_search_ids'] = [];
        if($request->search){
            $publicationSearchIds = User::select('id')
                                    ->whereHas('profile',function($q) use($request){
                                        $q->where('organization_name','LIKE',"%{$request->search}%");
                                    })
                                    ->where('user_type_id',3)
                                    ->where('status',1)
                                    ->where('payment_status',1)
                                    ->get()
                                    ->pluck('id')->toArray();   
            $input['publication_search_ids'] = $publicationSearchIds;
            
        }
        $newsList =  $this->successResponse($this->newsService->getNewsList($input));
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
        $allPublication = UserProfile::join('user_details', 'user_profile.user_id', 'user_details.id')
            ->where('user_details.user_type_id', 3)
            ->pluck('user_profile.organization_name', 'user_details.id');
       
        foreach($newsList->data as &$news) {
            foreach($news->categories as $category){
                $category->name = $allCategory[$category->category_id] ?? '';
            }
            foreach($news->publications as $publication){
                $publication->organization_name = $allPublication[$publication->publication_id] ?? '';
            }
        }
        return $this->successResponse(json_encode($newsList));
    }



    public function restoreNews(Request $request, $id){
            $data = [
                'id' => $id,
                'login_user_id' => $request->login_user_id
            ];
        return $this->successResponse($this->newsService->restoreNews($data));
    }

    public function deleteNews(Request $request, $id){
        $data = [
            'id' => $id,
            'login_user_id' => $request->login_user_id
        ];
        return $this->successResponse($this->newsService->deleteNews($data));
    }

    public function deleteNewsPermanent(Request $request, $id){
        $data = [
            'id' => $id, 
            'login_user_id' => $request->login_user_id
        ];
        return $this->successResponse($this->newsService->deleteNewsPermanent($data));
    }

    public function getNewsDetail(Request $request, $id){
        $news =  $this->successResponse($this->newsService->getNewsDetail($id, $request->all()));
        if(!$news->original) {
            return $this->errorResponse(trans('messages.news_not_found'), 422);    
        }
        $news = json_decode($news->original);
        $allCategory = CategoryMaster::pluck('name', 'id');
        $allPublication = UserProfile::join('user_details', 'user_profile.user_id', 'user_details.id')
            ->where('user_details.user_type_id', 3)
            ->pluck('user_profile.organization_name', 'user_details.id');
        if($news) {
            foreach($news->categories as $category){
                $category->name = $allCategory[$category->category_id] ?? '';
            }
            foreach($news->publications as $publication){
                $publication->organization_name = $allPublication[$publication->publication_id] ?? '';
            }
        }
        return $this->successResponse(json_encode($news));
    }

    public function updateNews(Request $request, $id){
        return $this->successResponse($this->newsService->updateNews($request, $id));
    }
}    
