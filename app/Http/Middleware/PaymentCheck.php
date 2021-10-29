<?php

namespace App\Http\Middleware;

use Closure;

class PaymentCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->login_user_payment_status!=1){
           return response()->json(['error' => 'Please complete payment.'], 403);
        }
        return $next($request);
    }
}
