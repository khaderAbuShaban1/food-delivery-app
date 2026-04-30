<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('restaurant::login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('restaurant')->attempt($credentials, $request->filled('remember'))) {
            $restaurant = Auth::guard('restaurant')->user();
            
            if (!$restaurant->is_active) {
                Auth::guard('restaurant')->logout();
                $request->session()->invalidate();
                return back()->with('error', 'تم حظر هذا الحساب')->withInput($request->only('email'));
            }
            
            $request->session()->regenerate();
            return redirect()->intended(route('restaurant.dashboard'));
        }

        return back()->with('error', 'بيانات الاعتماد غير صحيحة')->withInput($request->only('email'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('restaurant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('restaurant.login');
    }
}