<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid credentials')->withInput($request->only('email'));
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        session()->put('user', $user);
        session()->put('api_token', $token);

        return redirect()->route('restaurant.dashboard');
    }

    public function logout(): RedirectResponse
    {
        $user = session()->get('user');
        if ($user) {
            $dbUser = User::find($user['id']);
            if ($dbUser) {
                $dbUser->currentAccessToken()->delete();
            }
        }
        
        session()->forget('user');
        session()->forget('api_token');
        
        return redirect()->route('restaurant.login');
    }
}
