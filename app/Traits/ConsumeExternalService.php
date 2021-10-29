<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ConsumeExternalService
{
    /**
     * Send request to any service
     * @param $method
     * @param $requestUrl
     * @param array $formParams
     * @param array $headers
     * @return string
     */

    public function performRequest($method, $requestUrl, $formParams = [], $headers = [], $body = '',$multipart =[], $queryString=[]){

        $client = new Client([
            'base_uri'  =>  $this->baseUri,
        ]);
        $headers['Authorization'] = $headers['Authorization'] ?? '';
        $headers['Accept'] = $headers['Accept']??"application/json";
        
        
        if(isset($this->secret) && empty($headers['Authorization'])){
            $headers['Authorization'] = $this->secret;
        }
        
        if(!empty($body)){
            $params=[
                'body' => $body,
                'headers'     => $headers,
            ]; 
        }elseif(!empty($multipart)){
            $params=[
                'multipart' => $multipart,
                'headers'     => $headers,
            ];
        }else{
            $params=[
                'form_params' => $formParams,
                'headers'     => $headers,
                'query' => $queryString,
            ];
        }
        $response = $client->request($method, $requestUrl, $params);
        return $response->getBody()->getContents();
    }

    
}