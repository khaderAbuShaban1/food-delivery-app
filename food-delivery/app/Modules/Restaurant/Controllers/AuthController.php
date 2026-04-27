<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $restaurant = Restaurant::where('email', $request->email)->first();

        if (!$restaurant || !Hash::check($request->password, $restaurant->password)) {
            return back()->with('error', 'Invalid credentials')->withInput($request->only('email'));
        }

        session()->put('restaurant', $restaurant);

        return redirect()->route('restaurant.dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget('restaurant');
        
        return redirect()->route('restaurant.login');
    }
}