<?php

namespace App\Services;

use App\Traits\ConsumeExternalService;
use Illuminate\Http\Request;

class NewsService
{
    use ConsumeExternalService;

    /**
     * The base uri to consume authors service
     * @var string
     */
    public $baseUri;

    /**
     * Authorization secret to pass to author api
     * @var string
     */
    public $secret;

    public function __construct()
    {   
        $this->baseUri = config('services.news.base_uri');
        $this->secret = config('services.news.secret');
    }

    /**
     * store/create news
     */
    public function storeNews(Request $data)
    {   
        $headers=[
        'Content-Type' => 'multipart/form-data',
        //'authorization'=>$data->header('authorization')
        ];
        $data=$data->all();
        $multipart=[];
        foreach ($data as $key => $value) {
            if ($key != 'attachment') {
                if(is_array($value)){
                    foreach($value as $v){
                        $multipart[] = [
                            'name'=>$key.'[]',
                            'contents'=>$v,
                        ];
                    }
                }else{
                    $multipart[] = [
                        'name'=>$key,
                        'contents'=>$value
                    ];
                }
            }
        }

        $attachment = $data['attachment'] ?? '';
        if (!empty($attachment)) {
            foreach ($attachment as $key => $attch) {
                $multipart[] = [
                    'name'=>'attachment[]',
                    'contents'=>fopen($attch->getPathName(), 'r'),
                    'filename'=>$attch->getClientOriginalName()
                ];
            }
        }

       
        return $this->performRequest('POST', 'company/news', [], [], '',$multipart);
    }

    public function getDeletedNewsList($data, $headers=[]) {
        $queryString = [
    		'login_user_id'=>$data->login_user_id
    	];
        return $this->performRequest('GET', 'company/deleted-news',[],$headers,'',[],$queryString);
    }

    public function getNewsList($data, $headers=[]) {
       return $this->performRequest('GET', 'company/news',[],$headers,'',[],$data);
    }

    public function restoreNews($data, $headers=[]) {
        return $this->performRequest('POST', 'company/news/'.$data['id'].'/restore',$data,$headers);
    }

    public function deleteNews($data, $headers=[]) {
        return $this->performRequest('POST', 'company/news/'.$data['id'].'/delete',$data,$headers);
    }

    public function deleteNewsPermanent($data, $headers=[]) {
        return $this->performRequest('POST', 'company/news/'.$data['id'].'/forever-delete',$data,$headers);
    }

    public function getNewsDetail($id,$data,$headers=[]) {
        return $this->performRequest('GET', 'company/news/'.$id,[],$headers,'',[],$data);
    }

    public function updateNews(Request $data, $id)
    {   
        $headers=[
        'Content-Type' => 'multipart/form-data',
        //'authorization'=>$data->header('authorization')
        ];
        $data=$data->all();
        $multipart=[];
        foreach ($data as $key => $value) {
            if ($key != 'attachment') {
                if(is_array($value)){
                    foreach($value as $v){
                        $multipart[] = [
                            'name'=>$key.'[]',
                            'contents'=>$v,
                        ];
                    }
                }else{
                    $multipart[] = [
                        'name'=>$key,
                        'contents'=>$value
                    ];
                }
            }
        }

        $attachment = $data['attachment'] ?? '';
        if (!empty($attachment)) {
            foreach ($attachment as $key => $attch) {
                $multipart[] = [
                    'name'=>'attachment[]',
                    'contents'=>fopen($attch->getPathName(), 'r'),
                    'filename'=>$attch->getClientOriginalName()
                ];
            }
        }
        return $this->performRequest('POST', 'company/news/'.$id, [], [], '',$multipart);
    }
    
    /**
     * get News list
     */
    public function publication_viewAllRequests($data,$headers=[]) {
        return $this->performRequest('GET', 'publication/news',[],$headers,'',[],$data);
    }
    
    public function publication_viewNews($data,$headers=[]) {
        return $this->performRequest('GET', 'publication/news/'.$data['news_id'],[],$headers,'',[],$data);
    }
    
    public function publication_acceptStatus($data, $headers=[]) {
        return $this->performRequest('POST', 'publication/news/'.$data['news_id'].'/accept',$data,$headers);
    }
    
    public function publication_readyForPublishStatus($data, $headers=[]) {
        return $this->performRequest('POST', 'publication/news/'.$data['news_id'].'/readyforpublish',$data,$headers);
    }
    
    public function publication_rejectStatus($data, $headers=[]) {
        return $this->performRequest('POST', 'publication/news/'.$data['news_id'].'/reject',$data,$headers);
    }
    
    public function publication_revokeStatus($data, $headers=[]) {
        return $this->performRequest('POST', 'publication/news/'.$data['news_id'].'/revoke',$data,$headers);
    }
    
    public function publication_updatePublishingDate($data, $headers=[]) {
        return $this->performRequest('POST', 'publication/news/'.$data['news_id'].'/publishing-date',$data,$headers);
    }
    
    public function chat_getCompanyNewsByID($data, $headers=[]) {
        return $this->performRequest('GET', 'publication/chat/company/'.$data['company_id'],[],$headers,'',[],$data);
    }
    
    public function chat_getNewsByPublicationID($data, $headers=[]) {
        return $this->performRequest('GET', 'publication/chat/news/'.$data['publication_id'],[],$headers,'',[],$data);
    }

    public function getNewsCount($data,$headers=[]) {
        return $this->performRequest('GET', 'publication/news/'.$data['publication_id'].'/get-news-count',[],$headers,'',[],$data);
    }

    public function getNewsByPublicationId($data,$headers=[]) {
        return $this->performRequest('GET', 'chat/publication/list-news/'.$data['publication_id'],[],$headers,'',[],$data);
    }

    public function getActivePublicationId($data,$headers=[]) {
        return $this->performRequest('GET', 'chat/get-active-publication',[],$headers,'',[],$data);
    }
    
    public function company_getNewsCount($data,$headers=[]) {
        return $this->performRequest('GET', 'company/news/'.$data['id'].'/get-news-count',[],$headers,'',[],$data);
    }
}