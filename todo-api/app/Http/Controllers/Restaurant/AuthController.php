<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('restaurant.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])
            ->where('role', 'restaurant')
            ->first();

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Invalid credentials or not a restaurant account.',
            ])->onlyInput('email');
        }

        Auth::login($user);

        return redirect()->route('restaurant.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('restaurant.login');
    }
}