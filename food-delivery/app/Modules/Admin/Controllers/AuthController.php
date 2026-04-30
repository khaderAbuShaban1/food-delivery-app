<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin::login');
    }

    public function login(Request $request): RedirectResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        $limit = (int) $settings->get('security', 'login_attempt_limit', 5);
        $throttleKey = Str::lower((string) $request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, max(3, $limit))) {
            return back()->with('error', 'عدد محاولات تسجيل الدخول تجاوز الحد المسموح، حاول لاحقاً')->withInput($request->only('email'));
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials, $request->filled('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);
        return back()->with('error', 'بيانات الاعتماد غير صحيحة')->withInput($request->only('email'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }
}