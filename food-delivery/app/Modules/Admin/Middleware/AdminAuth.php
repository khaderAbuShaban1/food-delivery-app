<?php

namespace App\Modules\Admin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('admin')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}