<?php

return [
    'api_auth' => [
        'base_uri'  =>  env('AUTH_BASE_URI'),
        'secret'  =>  "Basic ".base64_encode(env("AUTH_CLIENT_USER").":".env("AUTH_CLIENT_PASS")),
        'auth_base_user' => env('AUTH_BASE_USER'),
        'auth_base_pass' => env('AUTH_BASE_PASS'),
        'auth_client_user' => env('AUTH_CLIENT_USER'),
        'auth_client_pass' => env('AUTH_CLIENT_PASS'),
        
    ],
    'location' => [
        'base_uri'  =>  env('SERVICE_LOCATION_BASE_URL'),
        'secret'  =>  "Basic ".base64_encode(env("AUTH_CLIENT_USER").":".env("AUTH_CLIENT_PASS")),        
    ],
    'news' => [
        'base_uri'  =>  env('SERVICE_NEWS_BASE_URL'),
        'secret'  =>  "Basic ".base64_encode(env("AUTH_CLIENT_USER").":".env("AUTH_CLIENT_PASS")),        
    ],
    'chat' => [
        'base_uri'  =>  env('SERVICE_CHAT_BASE_URL'),
        'secret'  =>  "Basic ".base64_encode(env("AUTH_CLIENT_USER").":".env("AUTH_CLIENT_PASS")),        
    ],
    'registration_email_verification_url'=>env('REGISTRATION_EMAIL_VERIFICATION_URL'),
    'reset_forgot_password_url'=>env('RESET_FORGOT_PASSWORD_URL'),
    'sms_api' => [
        'base_uri'  =>  env('SMS_API_BASE_URL'),
        'api_key'=> env('SMS_API_KEY'),
        'sender_id'  =>  env("SMS_SENDER_ID"),
    ],
    'admin'=>[
        'reset_forgot_password_url'=>env('ADMIN_RESET_FORGOT_PASSWORD_URL'),
    ]
    
];