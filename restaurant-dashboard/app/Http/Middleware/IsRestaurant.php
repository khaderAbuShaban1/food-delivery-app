<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsRestaurant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session()->get('user');

        if (!$user || ($user['role'] ?? null) !== 'restaurant') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}