<?php

namespace App\Modules\Restaurant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestaurantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user')) {
            return redirect()->route('restaurant.login');
        }

        return $next($request);
    }
}