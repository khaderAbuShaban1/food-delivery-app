<?php

namespace App\Modules\Restaurant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestaurantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('restaurant')->check()) {
            return redirect()->route('restaurant.login');
        }

        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant || !$restaurant->is_active) {
            Auth::guard('restaurant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('restaurant.login')->with('error', 'تم حظر هذا الحساب');
        }

        return $next($request);
    }
}