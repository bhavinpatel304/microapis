<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use App\Services\AuthApiService;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        /*$this->app['auth']->viaRequest('api', function ($request) {
            if ($request->input('api_token')) {
                return User::where('api_token', $request->input('api_token'))->first();
            }
        });*/

        /*$this->app['auth']->viaRequest('api', function ($request) {
            if ($request->header('Authorization')) {
                $key = explode(' ',$request->header('Authorization'));
                $user = User::where('api_key', $key[1])->first();
                if(!empty($user)){
                    $request->request->add(['userid' => $user->id]);
                }
                return $user;
            }
        });*/
        
        $this->app['auth']->viaRequest('api', function ($request) {
            $error = false;
            $client = new Client([
                "base_uri"=>config('services.api_auth.base_uri'),
            ]);            
            
            $token = ltrim($request->header('Authorization'),"Bearer");
            $token = trim($token);
           
            try {
                $authApiService = new AuthApiService;
                $response = $authApiService->checkAuthTokenDetail($token);
                //dd($response);
            } catch (\Exception $e) {

                $error = true;
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    //set error log
                }
            }
               
            if ($error == false) {
                $output = json_decode($response);
                    try{
                        //$getUserId = json_decode($authApiService->checkUserExists($output->data->user_name));
                        $getUserId = json_decode($authApiService->getOwnProfile($token));
                        $getUserId = $getUserId->data->id ?? '';
                        $user = User::where('user_uuid', $getUserId)->first();
                        if(empty(!$user)){
                            $request->request->add([
                                'login_user_id' => $user->id,
                                'login_user_type_id'=>$user->user_type_id,
                                'login_user_uuid'=>$user->user_uuid,
                                'login_user_payment_status'=>$user->payment_status,
                                'login_user_document_status'=>$user->document_status
                            ]);
                            return $user;
                        }
                    }catch(\Exception $e){
                      
                    }    
                    
               
            }
        });






    }
}
