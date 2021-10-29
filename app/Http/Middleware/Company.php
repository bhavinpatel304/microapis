<?php

namespace App\Http\Middleware;

use Closure;

class Company
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
        if($request->login_user_type_id!=2){
            return response()->json(['error' => 'forbidden'], 403);
        }
        return $next($request);
    }
}
