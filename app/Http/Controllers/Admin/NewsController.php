<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CategoryMaster;
use App\Traits\ApiResponser;
use GuzzleHttp\Client;
use Validator;
use DB;
use Carbon\Carbon;


class NewsController extends Controller
{
    use ApiResponser;    
    
    public function __construct()
    {
    }
    
    public function storeCategory(Request $request)
    {
        $rules = [
            'name' => 'required|unique:category_mst,name|max:50',
            'colour' => 'required|unique:category_mst,colour|size:7'
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 400);
        }
        $login_user_id = $request->login_user_id;
        
        $category = CategoryMaster::create([
            'name' => $request->name,
            'colour' => $request->colour,
            'status' => 1,
            'created_at' => Carbon::now(),
            'created_by' => $login_user_id,
            'updated_by' => $login_user_id,
        ]);
        
        return $this->successResponse($category);
    }
    
    public function viewAllCategory(Request $request)
    { 
        $categories = CategoryMaster::select('id','name','colour','status')->paginate(10);
        return $this->successResponse($categories);
    }

    public function updateCategory(Request $request)
    {
        $rules = [
            'category_id' => 'required',

            'category_name' =>  'required|max:50|unique:category_mst,name,' . $request->category_id . ',id',

            'colour' => 'required|size:7||unique:category_mst,colour,' . $request->category_id . ',id',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 400);
        }
        $login_user_id = $request->login_user_id;

        $categoryData = CategoryMaster::where('id',$request->category_id)
                    ->first();
            
        if(empty($categoryData)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
        
        $categoryData->name = $request->category_name;
        $categoryData->colour = $request->colour;
        $categoryData->updated_by = $login_user_id;
        $categoryData->updated_at = Carbon::now();
        $categoryData->update();
        $res = ["success"=>trans('messages.success_edited')];
        return $this->successResponse($res);
    }

    public function deleteCategory(Request $request)
    {
        $rules = [
            'category_id' => 'required'
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
        $login_user_id = $request->login_user_id;

        $categoryData = CategoryMaster::where('id',$request->category_id)
                    ->first();
            
        if(empty($categoryData)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }

        if($categoryData->status==1){
             return $this->errorResponse('can not delete active category.',500);
        }
        
        $time_now = Carbon::now();
        //$categoryData->status = 0;
        //$categoryData->updated_by = $login_user_id;
        //$categoryData->updated_at = $time_now;
        //$categoryData->deleted_at = $time_now;
        $categoryData->delete();
        $res = ["success"=>trans('messages.success_deleted')];
        return $this->successResponse($res);
    }
    
    public function changeCategoryStatus(Request $request)
    {
        $rules = [
            'category_id' => 'required|numeric'
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->errorResponse($validate->errors(), 422);
        }
        $login_user_id = $request->login_user_id;

        $categoryData = CategoryMaster::where('id',$request->category_id)
                    ->first();
            
        if(empty($categoryData)){
            return $this->errorResponse(trans('messages.not_found'),404);
        }
        
        if($categoryData->status==1){
            $categoryData->status = 0;
        }
        else{
            $categoryData->status = 1;
        }
        
        
        $categoryData->updated_by = $login_user_id;
        $categoryData->updated_at = Carbon::now();
        
        $categoryData->update();
        $res = ["success"=>trans('messages.success_edited')];
        return $this->successResponse($res);
        
    }    
}    
