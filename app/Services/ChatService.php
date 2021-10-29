<?php

namespace App\Services;

use App\Traits\ConsumeExternalService;
use Illuminate\Http\Request;

class ChatService
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
        $this->baseUri = config('services.chat.base_uri');
        $this->secret = config('services.chat.secret');
    }

    public function getRecentPublication($data,$headers=[]) {
        return $this->performRequest('GET', 'publication/recent-publication/'.$data['publication_id'],[],$headers,'',[],$data);
    }
    
    /*
     * Usage => Login by Publication User - See recent chat 
     *          To see companies messages
     */
    public function publication_getRecentChat($data,$headers=[]) {
        return $this->performRequest('GET', 'publication/chat/recent-company',[],$headers,'',[],$data);
    }


    public function getLastDateOfNews($data,$headers=[]) {
        return $this->performRequest('POST', 'publication/get-last-time-of-news',$data,$headers);
    }

    
    public function publication_getLastMsgTime($data,$headers=[]) {
        return $this->performRequest('GET', 'publication/chat/lastmsg',[],$headers,'',[],$data);
    }
    
}