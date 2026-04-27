<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsRestaurant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || auth()->user()->role !== 'restaurant') {
            return redirect('/restaurant/login');
        }

        return $next($request);
    }
}