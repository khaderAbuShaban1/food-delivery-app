<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin::login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'admin')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'بيانات الاعتماد غير صحيحة')->withInput($request->only('email'));
        }

        session()->put('admin', $user);

        return redirect()->route('admin.dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget('admin');
        
        return redirect()->route('admin.login');
    }
}